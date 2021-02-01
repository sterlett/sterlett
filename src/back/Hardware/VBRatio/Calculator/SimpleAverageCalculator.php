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

namespace Sterlett\Hardware\VBRatio\Calculator;

use RuntimeException;
use Sterlett\Hardware\Price\SimpleAverageCalculator as PriceAverageCalculator;
use Sterlett\Hardware\VBRatio\CalculatorInterface;

/**
 * Performs V/B ratio calculation using simple average formula.
 *
 * Expects a benchmark value as a numeric variable.
 *
 * todo: unit test
 */
class SimpleAverageCalculator implements CalculatorInterface
{
    /**
     * Encapsulates logic for average amount calculation, for the defined price interface
     *
     * @var PriceAverageCalculator
     */
    private PriceAverageCalculator $priceAverageCalculator;

    /**
     * Defines the number of digits after the decimal place in the V/B ratio value
     *
     * @var int
     */
    private int $scale;

    /**
     * SimpleAverageCalculator constructor.
     *
     * @param PriceAverageCalculator $priceAverageCalculator Encapsulates logic for average amount calculation
     * @param int                    $scale                  Defines the number of digits after the decimal place
     */
    public function __construct(PriceAverageCalculator $priceAverageCalculator, int $scale = 0)
    {
        $this->priceAverageCalculator = $priceAverageCalculator;
        $this->scale                  = $scale;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRatio(iterable $hardwarePrices, string $benchmarkValue): string
    {
        $benchmarkValueNormalized = (float) $benchmarkValue;

        if ($benchmarkValueNormalized < 0.001) {
            $invalidBenchmarkValueMessage = sprintf(
                'Unable to calculate V/B ratio, invalid benchmark value: %s.',
                $benchmarkValue
            );

            throw new RuntimeException($invalidBenchmarkValueMessage);
        }

        $priceAmountAverage = $this->priceAverageCalculator->calculateAverage($hardwarePrices, $this->scale);

        $ratioCalculated = bcdiv($benchmarkValue, $priceAmountAverage, $this->scale);

        return $ratioCalculated;
    }
}
