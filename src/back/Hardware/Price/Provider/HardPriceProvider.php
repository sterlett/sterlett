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

namespace Sterlett\Hardware\Price\Provider;

use Exception;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\HardPrice\Item\Loader as ItemLoader;
use Sterlett\HardPrice\Item\ReadableStorageInterface as ItemStorageInterface;
use Sterlett\HardPrice\Price\CollectorInterface as PriceCollectorInterface;
use Sterlett\HardPrice\Price\Extractor as PriceExtractor;
use Sterlett\HardPrice\Response\Reducer as ResponseReducer;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;
use Traversable;

/**
 * Obtains a list with hardware prices from the HardPrice website
 *
 * @see https://hardprice.ru
 */
class HardPriceProvider implements ProviderInterface
{
    /**
     * Extracts a list with available hardware items for data queries to the HardPrice website
     *
     * @var ItemLoader
     */
    private ItemLoader $itemLoader;

    /**
     * @var PriceExtractor
     */
    private PriceExtractor $priceExtractor;

    /**
     * Applies a reduce function to the list of response promises for collecting stage
     *
     * @var ResponseReducer
     */
    private ResponseReducer $responseReducer;

    /**
     * Collects price responses and builds an iterator to access price data, keyed by the specific hardware identifiers
     *
     * @var PriceCollectorInterface
     */
    private PriceCollectorInterface $priceCollector;

    /**
     * HardPriceProvider constructor.
     *
     * @param ItemLoader              $itemLoader      Extracts a list with available hardware items
     * @param PriceExtractor          $priceExtractor
     * @param ResponseReducer         $responseReducer Applies a reduce function to the list of response promises
     * @param PriceCollectorInterface $priceCollector  Collects price responses and builds price data iterator
     */
    public function __construct(
        ItemLoader $itemLoader,
        PriceExtractor $priceExtractor,
        ResponseReducer $responseReducer,
        PriceCollectorInterface $priceCollector
    ) {
        $this->itemLoader      = $itemLoader;
        $this->priceExtractor  = $priceExtractor;
        $this->responseReducer = $responseReducer;
        $this->priceCollector  = $priceCollector;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        $itemStoragePromise = $this->itemLoader->loadItems();

        // extracting a list with available hardware identifiers for price fetching requests.
        $idListPromise = $itemStoragePromise->then(
            function (ItemStorageInterface $itemStorage) {
                return (function () use ($itemStorage) {
                    $hardwareItems = $itemStorage->all();

                    foreach ($hardwareItems as $hardwareItem) {
                        $itemIdentifier = $hardwareItem->getIdentifier();

                        yield $itemIdentifier;
                    }
                })();
            },
            // rejections will be propagated from the item loader
        );

        $idListPromise->then(
            function (iterable $hardwareIdentifiers) use ($retrievingDeferred) {
                try {
                    // querying data when the hardware identifiers are ready.
                    $priceListPromise = $this->onHardwareIdentifiers($hardwareIdentifiers);

                    // transferring responsibility (resolver) from the retrieving process to the requesting process.
                    // we are closing the promise resolving chain at this point.
                    $retrievingDeferred->resolve($priceListPromise);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to retrieve prices (requests).', 0, $exception);

                    $retrievingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices (ids, auth).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $priceListPromise = $retrievingDeferred->promise();

        return $priceListPromise;
    }

    /**
     * Sends requests for hardware price data and collects incoming responses using MapReduce pattern. Returned promise
     * will be resolved to the iterable list of hardware prices (Traversable<PriceInterface> or PriceInterface[]),
     * keyed by their identifiers.
     *
     * @param Traversable<int>|int[] $hardwareIdentifiers A list with hardware identifiers for price fetching
     *
     * @return PromiseInterface<iterable>
     */
    private function onHardwareIdentifiers(iterable $hardwareIdentifiers): PromiseInterface
    {
        $requestingDeferred = new Deferred();

        // map stage: acquiring a request promise for each hardware identifier and applying map function, to collect all
        // related data for the given identifiers at the reduce stage.
        $promisesMapped = [];

        foreach ($hardwareIdentifiers as $hardwareIdentifier) {
            // todo: encapsulate reduce logic within extractor
            $responsePromise = $this->priceExtractor->extractPrice($hardwareIdentifier);

            // map function: list(promise, id) -> list(response, id).
            $promiseMapped = $responsePromise->then(
                function (ResponseInterface $response) use ($hardwareIdentifier) {
                    return [$response, $hardwareIdentifier];
                },
                function (Throwable $rejectionReason) {
                    throw new RuntimeException('Unable to apply map function to the response.', 0, $rejectionReason);
                }
            );

            $promisesMapped[] = $promiseMapped;
        }

        // reduce stage: collecting all responses and aggregating them into a single data structure for centralized
        // processing with the "onFulfilled" callbacks.
        $reducePromise = $this->responseReducer->reduce($promisesMapped);

        $reducePromise->then(
            function (iterable $responseListById) use ($requestingDeferred) {
                try {
                    $hardwarePrices = $this->priceCollector->makeIterator($responseListById);

                    $requestingDeferred->resolve($hardwarePrices);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to collect price responses.', 0, $exception);

                    $requestingDeferred->reject($reason);
                }
            },
            function (Exception $rejectionReason) use ($requestingDeferred) {
                $reason = new RuntimeException('Unable to reduce price responses.', 0, $rejectionReason);

                $requestingDeferred->reject($reason);
            }
        );

        $priceListPromise = $requestingDeferred->promise();

        return $priceListPromise;
    }
}
