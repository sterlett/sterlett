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

namespace Sterlett\HardPrice\Price\Collector;

use Iterator;
use Sterlett\HardPrice\Price\CollectorInterface;
use Sterlett\HardPrice\Price\MergingIterator;

/**
 * Makes a deterministic iterator for the hardware price collection, which provides complete pairs with unique keys
 * (hardware identifiers) on each iteration and accumulated values (price collection).
 *
 * Acts like a data bufferer for blocking environment, to prevent multiple iterations with the same hardware identifier
 * as a key.
 */
class MergingCollector implements CollectorInterface
{
    /**
     * Base implementation that collects responses with raw price data from the website
     *
     * @var CollectorInterface
     */
    private CollectorInterface $priceCollector;

    /**
     * MergingCollector constructor.
     *
     * @param CollectorInterface $priceCollector Base implementation that collects responses with raw price data
     */
    public function __construct(CollectorInterface $priceCollector)
    {
        $this->priceCollector = $priceCollector;
    }

    /**
     * {@inheritDoc}
     */
    public function makeIterator(iterable $responseListById): Iterator
    {
        $hardwarePrices = $this->priceCollector->makeIterator($responseListById);

        $priceIterator = new MergingIterator($hardwarePrices);

        return $priceIterator;
    }
}
