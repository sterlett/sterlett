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

namespace Sterlett\Hardware\VBRatio;

use RuntimeException;
use Sterlett\Hardware\PriceInterface;

/**
 * Provides implementation for the Value/Benchmark rating calculation
 */
interface CalculatorInterface
{
    /**
     * Returns a V/B rating value that will be calculated for the hardware item by the given price records
     * and benchmark results. Retval represented by a numeric string.
     *
     * The price records collection is expected as an instance of Traversable<PriceInterface> or PriceInterface[].
     *
     * @param iterable $hardwarePrices A collection of hardware prices
     * @param string   $benchmarkValue Benchmark result for the hardware item
     *
     * @return string
     *
     * @throws RuntimeException Whenever an error has been occurred during V/B ratio calculation
     *
     * @see PriceInterface
     */
    public function calculateRatio(iterable $hardwarePrices, string $benchmarkValue): string;
}
