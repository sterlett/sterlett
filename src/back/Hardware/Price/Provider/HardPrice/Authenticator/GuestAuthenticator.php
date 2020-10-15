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

namespace Sterlett\Hardware\Price\Provider\HardPrice\Authenticator;

use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\ClientInterface;
use Sterlett\Hardware\Price\Provider\HardPrice\Authentication;
use Sterlett\Hardware\Price\Provider\HardPrice\ChromiumHeaders;
use Sterlett\Hardware\Price\Provider\HardPrice\CsrfTokenParser;
use Throwable;

/**
 * Performs authentication for the subsequent requests to mimic guest activity
 */
class GuestAuthenticator
{
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
     * URI for authentication context building
     *
     * @var string
     */
    private string $authenticationUri;

    /**
     * GuestAuthenticator constructor.
     *
     * @param ClientInterface $httpClient        Requests data from the external source
     * @param CsrfTokenParser $csrfTokenParser   Extracts a CSRF token from the website page content
     * @param string          $authenticationUri URI for authentication context building
     */
    public function __construct(
        ClientInterface $httpClient,
        CsrfTokenParser $csrfTokenParser,
        string $authenticationUri
    ) {
        $this->httpClient        = $httpClient;
        $this->csrfTokenParser   = $csrfTokenParser;
        $this->authenticationUri = $authenticationUri;
    }

    /**
     * Returns a promise that resolves to an object, representing context of interactive session on the website
     *
     * @return PromiseInterface<Authentication>
     */
    public function authenticate(): PromiseInterface
    {
        $authenticationDeferred = new Deferred();

        $requestHeaders  = ChromiumHeaders::makeFrom([]);
        $responsePromise = $this->httpClient->request('GET', $this->authenticationUri, $requestHeaders);

        $responsePromise->then(
            function (ResponseInterface $response) use ($authenticationDeferred) {
                try {
                    $authentication = $this->onResponseSuccess($response);

                    $authenticationDeferred->resolve($authentication);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to authenticate (deserialization).', 0, $exception);

                    $authenticationDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($authenticationDeferred) {
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

        if (!is_string($cookieAggregatedString) || empty($cookieAggregatedString)) {
            // todo: response logging

            throw new RuntimeException('No cookies for authentication present.');
        }

        $cookieAggregatedParts = explode(';', $cookieAggregatedString);
        $sessionToken          = $cookieAggregatedParts[0];

        $authentication->addCookie($sessionToken);

        // resolving csrf token.
        $bodyAsString = (string) $response->getBody();
        $csrfToken    = $this->csrfTokenParser->parse($bodyAsString);

        $authentication->setCsrfToken($csrfToken);

        return $authentication;
    }
}
