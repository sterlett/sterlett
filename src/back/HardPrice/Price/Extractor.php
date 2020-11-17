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

namespace Sterlett\HardPrice\Price;

use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Sterlett\HardPrice\Authentication;
use Sterlett\HardPrice\Authenticator\GuestAuthenticator;
use Sterlett\HardPrice\Item\ReadableStorageInterface as ItemStorageInterface;
use Sterlett\HardPrice\Price\Requester as PriceRequester;
use Throwable;

/**
 * Performs price fetching for the hardware items, using configured authenticator and request builder
 */
class Extractor
{
    /**
     * @var TimeIssuerInterface
     */
    private TimeIssuerInterface $scrapingThread;

    /**
     * Holds hardware items data, available for price fetching
     *
     * @var ItemStorageInterface
     */
    private ItemStorageInterface $itemStorage;

    /**
     * Performs authentication for the subsequent requests to mimic guest activity
     *
     * @var GuestAuthenticator
     */
    private GuestAuthenticator $requestAuthenticator;

    /**
     * Sends price data fetching requests to the HardPrice endpoint
     *
     * @var PriceRequester
     */
    private PriceRequester $priceRequester;

    /**
     * Extractor constructor.
     *
     * @param TimeIssuerInterface  $scrapingThread
     * @param ItemStorageInterface $itemStorage          Holds hardware items data, for authentication context building
     * @param GuestAuthenticator   $requestAuthenticator Performs authentication for the subsequent requests
     * @param PriceRequester       $priceRequester       Sends price data fetching requests
     */
    public function __construct(
        TimeIssuerInterface $scrapingThread,
        ItemStorageInterface $itemStorage,
        GuestAuthenticator $requestAuthenticator,
        Requester $priceRequester
    ) {
        $this->scrapingThread       = $scrapingThread;
        $this->itemStorage          = $itemStorage;
        $this->requestAuthenticator = $requestAuthenticator;
        $this->priceRequester       = $priceRequester;
    }

    /**
     * Returns a promise that resolves to the PSR-7 message, representing a response with hardware price data
     *
     * @param int $hardwareIdentifier Hardware item identifier for price fetching request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function extractPrice(int $hardwareIdentifier): PromiseInterface
    {
        $extractingDeferred = new Deferred();

        $hardwareItem = $this->itemStorage->require($hardwareIdentifier);

        $authenticationUriPath = $hardwareItem->getPageUri();
        $authenticationPromise = $this->requestAuthenticator->authenticate($authenticationUriPath);

        $authenticationPromise->then(
            function (Authentication $authentication) use ($extractingDeferred, $hardwareIdentifier) {
                try {
                    $responsePromise = $this->onAuthentication($hardwareIdentifier, $authentication);

                    $extractingDeferred->resolve($responsePromise);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to request hardware prices.', 0, $exception);

                    $extractingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($extractingDeferred) {
                $reason = new RuntimeException('Unable to authenticate price fetching request.', 0, $rejectionReason);
                $extractingDeferred->reject($reason);
            }
        );

        $responsePromise = $extractingDeferred->promise();

        return $responsePromise;
    }

    /**
     * Runs price fetching logic when the time frame in the shared scraping routine is acquired
     *
     * @param int            $hardwareIdentifier Hardware item identifier for price fetching request
     * @param Authentication $authentication     Holds authentication data payload
     *
     * @return PromiseInterface<ResponseInterface>
     */
    private function onAuthentication(int $hardwareIdentifier, Authentication $authentication): PromiseInterface
    {
        $actionDeferred = new Deferred();

        $timePromise = $this->scrapingThread->getTime();

        $timePromise->then(
            function () use ($actionDeferred, $hardwareIdentifier, $authentication) {
                try {
                    $responsePromise = $this->onTimeAllocated($hardwareIdentifier, $authentication);

                    $actionDeferred->resolve($responsePromise);
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
     * Performs a request using provided hardware identifier and authentication payload
     *
     * @param int            $hardwareIdentifier Hardware item identifier for price fetching request
     * @param Authentication $authentication     Holds authentication data payload
     *
     * @return PromiseInterface<ResponseInterface>
     */
    private function onTimeAllocated(int $hardwareIdentifier, Authentication $authentication): PromiseInterface
    {
        $fetchingDeferred = new Deferred();

        $responsePromise = $this->priceRequester->requestPrice($hardwareIdentifier, $authentication);

        $responsePromise->then(
            function (ResponseInterface $response) use ($fetchingDeferred) {
                try {
                    $this->scrapingThread->release();

                    $fetchingDeferred->resolve($response);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to request hardware prices.', 0, $exception);

                    $fetchingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($fetchingDeferred) {
                $this->scrapingThread->release();

                $reason = new RuntimeException('Unable to send price fetching request.', 0, $rejectionReason);
                $fetchingDeferred->reject($reason);
            }
        );

        $responsePromise = $fetchingDeferred->promise();

        return $responsePromise;
    }
}
