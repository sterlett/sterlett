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
use Throwable;

/**
 * Extracts a list with available hardware items from the HardPrice website
 */
class Loader
{
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
     * @param Requester        $itemRequester Sends a request to get available hardware items from the website
     * @param Parser           $itemParser    Transforms hardware items data from the raw format to the list of DTOs
     * @param StorageInterface $itemStorage   The destination to which hardware items will be saved
     */
    public function __construct(Requester $itemRequester, Parser $itemParser, StorageInterface $itemStorage)
    {
        $this->itemRequester = $itemRequester;
        $this->itemParser    = $itemParser;
        $this->itemStorage   = $itemStorage;
    }

    /**
     * Returns a promise that resolves to a reference to the data structure with hardware items
     *
     * @return PromiseInterface<ReadableStorageInterface>
     */
    public function loadItems(): PromiseInterface
    {
        $loadingDeferred = new Deferred();

        $responsePromise = $this->itemRequester->requestItems();

        $responsePromise->then(
            function (ResponseInterface $response) use ($loadingDeferred) {
                try {
                    $this->onResponseSuccess($response);

                    // client code will receive a reference to the data structure with hardware items.
                    $loadingDeferred->resolve($this->itemStorage);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException(
                        'Unable to load hardware items (deserialization).',
                        0,
                        $exception
                    );

                    $loadingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($loadingDeferred) {
                $reason = new RuntimeException(
                    'Unable to load hardware items (request).',
                    0,
                    $rejectionReason
                );

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
