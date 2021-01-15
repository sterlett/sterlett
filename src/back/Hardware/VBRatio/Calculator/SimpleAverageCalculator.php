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
use Sterlett\Hardware\PriceInterface;
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

        $priceSum    = '0.00';
        $priceCount  = 0;
        $scaleNumber = 2;

        /** @var PriceInterface $price */
        foreach ($hardwarePrices as $price) {
            $priceDenormalized = $this->denormalizePrice($price);

            $priceSum = bcadd($priceSum, $priceDenormalized, $scaleNumber);
            ++$priceCount;
        }

        if ($priceCount < 1) {
            throw new RuntimeException('Unable to calculate V/B ratio, no price records.');
        }

        $priceCountAsString = (string) $priceCount;
        $priceAmountAverage = bcdiv($priceSum, $priceCountAsString, $scaleNumber);

        $ratioCalculated = bcdiv($priceAmountAverage, $benchmarkValue, $scaleNumber);

        // todo: beautifier wrapper

        return $ratioCalculated;
    }

    /**
     * Returns a denormalized, numeric price representation (precision is applied to the amount)
     *
     * @param PriceInterface $price Price record
     *
     * @return string
     */
    private function denormalizePrice(PriceInterface $price): string
    {
        $priceAmount    = $price->getAmount();
        $amountAsString = (string) $priceAmount;

        $pricePrecision = $price->getPrecision();

        if ($pricePrecision > 0) {
            $amountDenormalized = substr_replace($amountAsString, '.', -$pricePrecision, 0);
        } else {
            $amountDenormalized = $amountAsString;
        }

        return $amountDenormalized;
    }
}
