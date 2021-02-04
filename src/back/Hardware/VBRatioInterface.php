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

namespace Sterlett\Hardware;

/**
 * Represents a V/B calculation result for the specific hardware item on the market
 */
interface VBRatioInterface
{
    /**
     * Returns a collection of price records for the target hardware item, which is used in the calculation
     *
     * @return PriceInterface[]
     */
    public function getSourcePrices(): array;

    /**
     * Returns a related benchmark record
     *
     * @return BenchmarkInterface
     */
    public function getSourceBenchmark(): BenchmarkInterface;

    /**
     * Returns a calculated V/B rating value
     *
     * @return string
     */
    public function getValue(): string;
}
