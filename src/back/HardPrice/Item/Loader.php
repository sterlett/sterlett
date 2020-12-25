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

namespace Sterlett\HardPrice\Item;

use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Throwable;

/**
 * Extracts a list with available hardware items from the HardPrice website
 */
class Loader
{
    /**
     * Allocates a time frame in the shared scraping routine
     *
     * @var TimeIssuerInterface
     */
    private TimeIssuerInterface $scrapingThread;

    /**
     * Sends a request to get available hardware items from the website
     *
     * @var Requester
     */
    private Requester $itemRequester;

    /**
     * Transforms hardware items data from the raw format to the iterable list of normalized values (DTOs)
     *
     * @var Parser
     */
    private Parser $itemParser;

    /**
     * The destination to which hardware items will be saved for further processing
     *
     * @var StorageInterface
     */
    private StorageInterface $itemStorage;

    /**
     * Loader constructor.
     *
     * @param TimeIssuerInterface $scrapingThread Allocates a time frame in the shared scraping routine
     * @param Requester           $itemRequester  Sends a request to get available hardware items from the website
     * @param Parser              $itemParser     Transforms hardware items data from the raw format to the list of DTOs
     * @param StorageInterface    $itemStorage    The destination to which hardware items will be saved
     */
    public function __construct(
        TimeIssuerInterface $scrapingThread,
        Requester $itemRequester,
        Parser $itemParser,
        StorageInterface $itemStorage
    ) {
        $this->scrapingThread = $scrapingThread;
        $this->itemRequester  = $itemRequester;
        $this->itemParser     = $itemParser;
        $this->itemStorage    = $itemStorage;
    }

    /**
     * Returns a promise that resolves to a reference to the data structure with hardware items
     *
     * @return PromiseInterface<ReadableStorageInterface>
     */
    public function loadItems(): PromiseInterface
    {
        $actionDeferred = new Deferred();

        $timePromise = $this->scrapingThread->getTime();

        $timePromise->then(
            function () use ($actionDeferred) {
                try {
                    $itemStoragePromise = $this->onTimeAllocated();

                    $actionDeferred->resolve($itemStoragePromise);
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
     * Runs items loading logic when the time frame in the shared scraping routine is acquired
     *
     * @return PromiseInterface<ReadableStorageInterface>
     */
    private function onTimeAllocated(): PromiseInterface
    {
        $loadingDeferred = new Deferred();

        $responsePromise = $this->itemRequester->requestItems();

        $responsePromise->then(
            function (ResponseInterface $response) use ($loadingDeferred) {
                try {
                    $this->scrapingThread->release();

                    $this->onResponseSuccess($response);
                    // client code will receive a reference to the data structure with hardware items.
                    $loadingDeferred->resolve($this->itemStorage);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to load hardware items (deserialization).', 0, $exception);

                    $loadingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($loadingDeferred) {
                $this->scrapingThread->release();

                $reason = new RuntimeException('Unable to load hardware items (request).', 0, $rejectionReason);
                $loadingDeferred->reject($reason);
            }
        );

        $itemStoragePromise = $loadingDeferred->promise();

        return $itemStoragePromise;
    }

    /**
     * Prepares data storage for the new set of hardware items
     *
     * @param ResponseInterface $response PSR-7 response message with hardware items payload
     *
     * @return void
     */
    private function onResponseSuccess(ResponseInterface $response): void
    {
        $bodyAsString = (string) $response->getBody();

        $hardwareItems = $this->itemParser->parse($bodyAsString);

        $this->itemStorage->clear();

        foreach ($hardwareItems as $hardwareItem) {
            $this->itemStorage->add($hardwareItem);
        }
    }
}
