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

use Iterator;

/**
 * Collects responses with raw price data from the HardPrice website and builds an iterator to access it, using DTOs,
 * keyed by the specific hardware identifiers.
 */
interface CollectorInterface
{
    /**
     * Returns a traversable list with pairs {hardware identifier => price DTOs}. Expects an input structure that
     * contains raw data with hardware prices from the website.
     *
     * Input format example:
     * [
     *     2533 => [response1, response2, ... responseN],
     *     2900 => [response1]
     * ]
     *
     * An iterable list, representing an element of returning collection, will be Traversable<PriceInterface> or
     * PriceInterface[].
     *
     * @param iterable $responseListById A map {id => responses} with raw price data from the website
     *
     * @return Iterator<int, iterable>
     */
    public function makeIterator(iterable $responseListById): Iterator;
}
