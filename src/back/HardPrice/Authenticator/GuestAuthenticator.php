<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\HardPrice\Authenticator;

use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Sterlett\ClientInterface;
use Sterlett\HardPrice\Authentication;
use Sterlett\HardPrice\ChromiumHeaders;
use Sterlett\HardPrice\Csrf\TokenParser as CsrfTokenParser;
use Sterlett\HardPrice\SessionMemento;
use Throwable;

/**
 * Performs authentication for the subsequent requests to mimic guest activity
 */
class GuestAuthenticator
{
    /**
     * Allocates a time frame in the shared scraping routine (i.e. "virtual" thread)
     *
     * @var TimeIssuerInterface
     */
    private TimeIssuerInterface $scrapingThread;

    /**
     * Holds a shared context with authentication data (cookies, tokens, etc.) to maintain a single browsing session
     *
     * @var SessionMemento
     */
    private SessionMemento $sessionMemento;

    /**
     * Requests data from the external source
     *
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * Extracts a CSRF token from the website page content
     *
     * @var CsrfTokenParser
     */
    private CsrfTokenParser $csrfTokenParser;

    /**
     * Base URI for authentication context building
     *
     * @var string
     */
    private string $authenticationUriBase;

    /**
     * GuestAuthenticator constructor.
     *
     * @param TimeIssuerInterface $scrapingThread        Allocates a time frame in the shared scraping routine
     * @param SessionMemento      $sessionMemento        Holds authentication data to maintain a single browsing session
     * @param ClientInterface     $httpClient            Requests data from the external source
     * @param CsrfTokenParser     $csrfTokenParser       Extracts a CSRF token from the website page content
     * @param string              $authenticationUriBase Base URI for authentication context building
     */
    public function __construct(
        TimeIssuerInterface $scrapingThread,
        SessionMemento $sessionMemento,
        ClientInterface $httpClient,
        CsrfTokenParser $csrfTokenParser,
        string $authenticationUriBase
    ) {
        $this->scrapingThread        = $scrapingThread;
        $this->sessionMemento        = $sessionMemento;
        $this->httpClient            = $httpClient;
        $this->csrfTokenParser       = $csrfTokenParser;
        $this->authenticationUriBase = $authenticationUriBase;
    }

    /**
     * Returns a promise that resolves to an object, representing context of interactive session on the website
     *
     * @param string $authenticationUriPath Relative path on the website for authentication URI building
     *
     * @return PromiseInterface<Authentication>
     */
    public function authenticate(string $authenticationUriPath): PromiseInterface
    {
        $actionDeferred = new Deferred();

        $timePromise = $this->scrapingThread->getTime();

        $timePromise->then(
            function () use ($actionDeferred, $authenticationUriPath) {
                try {
                    $authenticationPromise = $this->onTimeAllocated($authenticationUriPath);

                    $actionDeferred->resolve($authenticationPromise);
                } catch (Throwable $exception) {
                    $this->scrapingThread->release();

                    $reason = new RuntimeException(
                        'Unable to use a time frame in the scraping routine.',
                        0,
                        $exception
                    );
                    $actionDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($actionDeferred) {
                $reason = new RuntimeException(
                    'Unable to allocate a time frame in the scraping routine (time issuer).',
                    0,
                    $rejectionReason
                );

                $actionDeferred->reject($reason);
            }
        );

        $actionPromise = $actionDeferred->promise();

        return $actionPromise;
    }

    /**
     * Runs authentication logic when the time frame in the shared scraping routine is acquired
     *
     * @param string $authenticationUriPath Relative path on the website for authentication URI building
     *
     * @return PromiseInterface<Authentication>
     */
    private function onTimeAllocated(string $authenticationUriPath): PromiseInterface
    {
        $authenticationDeferred = new Deferred();

        $authenticationUri = $this->authenticationUriBase . $authenticationUriPath;

        $browsingSession = $this->sessionMemento->getSession();
        $sessionHeaders  = [];

        if ($browsingSession instanceof Authentication) {
            $sessionCookies           = $browsingSession->getCookies();
            $sessionCookieAggregated  = implode(';', $sessionCookies);
            $sessionHeaders['Cookie'] = $sessionCookieAggregated;

            $csrfToken                      = $browsingSession->getCsrfToken();
            $sessionHeaders['X-CSRF-TOKEN'] = $csrfToken;
        }

        $requestHeaders = ChromiumHeaders::makeFrom($sessionHeaders);

        $responsePromise = $this->httpClient->request('GET', $authenticationUri, $requestHeaders);

        $responsePromise->then(
            function (ResponseInterface $response) use ($authenticationDeferred) {
                try {
                    $this->scrapingThread->release();

                    $authentication = $this->onResponseSuccess($response);
                    $authenticationDeferred->resolve($authentication);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to authenticate (deserialization).', 0, $exception);

                    $authenticationDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($authenticationDeferred) {
                $this->scrapingThread->release();

                $reason = new RuntimeException('Unable to authenticate (request).', 0, $rejectionReason);
                $authenticationDeferred->reject($reason);
            }
        );

        $authenticationPromise = $authenticationDeferred->promise();

        return $authenticationPromise;
    }

    /**
     * Builds and returns an authentication context, based on the given response
     *
     * @param ResponseInterface $response PSR-7 response message with authentication data payload
     *
     * @return Authentication
     */
    private function onResponseSuccess(ResponseInterface $response): Authentication
    {
        $authentication = new Authentication();

        // contains all cookies as a string with delimiter symbols; extracting session token.
        $cookieAggregatedString = $response->getHeaderLine('set-cookie');

        if (is_string($cookieAggregatedString) && !empty($cookieAggregatedString)) {
            // todo: info log record

            $cookieAggregatedParts = explode(';', $cookieAggregatedString);
            $sessionToken          = $cookieAggregatedParts[0];

            $authentication->addCookie($sessionToken);
        }

        // resolving csrf token.
        $bodyAsString = (string) $response->getBody();
        $csrfToken    = $this->csrfTokenParser->parse($bodyAsString);

        $authentication->setCsrfToken($csrfToken);

        $this->updateBrowsingSession($authentication);

        return $authentication;
    }

    /**
     * Updates shared browsing session (if has been changed) after authentication request
     *
     * @param Authentication $authentication A fresh authentication context from the recent request
     *
     * @return void
     */
    private function updateBrowsingSession(Authentication $authentication): void
    {
        $browsingSession = $this->sessionMemento->getSession();

        if (!$browsingSession instanceof Authentication) {
            $this->sessionMemento->setSession($authentication);

            return;
        }

        $csrfToken        = $authentication->getCsrfToken();
        $csrfTokenCurrent = $browsingSession->getCsrfToken();

        if (!empty($csrfToken) && $csrfToken !== $csrfTokenCurrent) {
            $browsingSession->setCsrfToken($csrfToken);

            $this->sessionMemento->setSession($browsingSession);
        }
    }
}
