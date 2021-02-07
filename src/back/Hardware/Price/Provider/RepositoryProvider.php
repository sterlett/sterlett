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

namespace Sterlett\Hardware\Price\Provider;

use DateTime;
use React\Promise\PromiseInterface;
use Sterlett\Hardware\Price\ProviderInterface;
use Sterlett\Hardware\Price\Repository as PriceRepository;
use Sterlett\Hardware\PriceInterface;
use Traversable;

/**
 * Provides hardware price data using the local storage
 */
class RepositoryProvider implements ProviderInterface
{
    /**
     * A service to interact with the local price records storage
     *
     * @var PriceRepository
     */
    private PriceRepository $priceRepository;

    /**
     * RepositoryProvider constructor.
     *
     * @param PriceRepository $priceRepository A service to interact with the local price records storage
     */
    public function __construct(PriceRepository $priceRepository)
    {
        $this->priceRepository = $priceRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        $dateFrom = new DateTime('-1 day');
        $dateTo   = new DateTime('now');

        $priceListPromise = $this->priceRepository
            // requesting data from the local storage.
            ->findByCreatedAt($dateFrom, $dateTo)
            // an additional pass to aggregate price records by hardware name (to maintain a provider contract).
            ->then(fn (iterable $hardwarePrices) => $this->aggregateByItemName($hardwarePrices))
        ;

        return $priceListPromise;
    }

    /**
     * Returns an array of price records, aggregated by the hardware name (for provider contract)
     *
     * @param Traversable<PriceInterface>|PriceInterface[] $hardwarePrices Price records from the local storage
     *
     * @return array
     */
    private function aggregateByItemName(iterable $hardwarePrices): array
    {
        $priceListByItemName = [];

        foreach ($hardwarePrices as $hardwarePrice) {
            $hardwareName = $hardwarePrice->getHardwareName();

            if (array_key_exists($hardwareName, $priceListByItemName)) {
                $priceListByItemName[$hardwareName][] = $hardwarePrice;

                continue;
            }

            $priceListByItemName[$hardwareName] = [$hardwarePrice];
        }

        return array_values($priceListByItemName);
    }
}
