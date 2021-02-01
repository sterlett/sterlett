<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Hardware\Price;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Dto\Hardware\Price;
use Throwable;
use Traversable;

/**
 * Finds price records for the different hardware items and saves them into the local storage
 */
class Retriever
{
    /**
     * Encapsulates the price retrieving algorithm (async approach)
     *
     * @var ProviderInterface
     */
    private ProviderInterface $priceProvider;

    /**
     * A storage with price records
     *
     * @var Repository
     */
    private Repository $priceRepository;

    /**
     * Retriever constructor.
     *
     * @param ProviderInterface $priceProvider   Encapsulates the price retrieving algorithm (async approach)
     * @param Repository        $priceRepository A storage with price records
     */
    public function __construct(ProviderInterface $priceProvider, Repository $priceRepository)
    {
        $this->priceProvider   = $priceProvider;
        $this->priceRepository = $priceRepository;
    }

    /**
     * Extracts hardware prices from the specified provider and saves them using a repository reference. Returns a
     * promise that will be resolved when the price retrieving process is complete (or errored).
     *
     * @return PromiseInterface<null>
     */
    public function retrievePrices(): PromiseInterface
    {
        $priceListPromise = $this->priceProvider->getPrices();

        $pricePersistencePromise = $priceListPromise->then(
            function (iterable $priceList) {
                foreach ($priceList as $hardwarePrices) {
                    $this->persistPrices($hardwarePrices);
                }
            },
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to find and save hardware prices (retriever).', 0, $rejectionReason);
            }
        );

        // todo: truly guarantee a successful persist action for each price record
        //  (MapReduce logic for database query promises is required)
        // it is not guaranteed for now (omitted).

        return $pricePersistencePromise;
    }

    /**
     * Saves price records into the local storage
     *
     * @param Traversable<Price>|Price[] $hardwarePrices A collection of prices for a single hardware item
     *
     * @return void
     */
    private function persistPrices(iterable $hardwarePrices): void
    {
        foreach ($hardwarePrices as $price) {
            $this->priceRepository->save($price);
        }
    }
}
