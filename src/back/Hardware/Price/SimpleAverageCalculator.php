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

use RuntimeException;
use Sterlett\Hardware\PriceInterface;

/**
 * Encapsulates logic for average amount calculation, for the defined price interface
 */
class SimpleAverageCalculator
{
    /**
     * Returns a calculated average amount for the given price DTOs.
     *
     * Price collection is expected as an instance of Traversable<PriceInterface> or PriceInterface[].
     *
     * @param iterable $hardwarePrices Input price objects
     * @param int      $scale          Defines the number of digits after the decimal place for the result value
     *
     * @return string
     */
    public function calculateAverage(iterable $hardwarePrices, int $scale = 0): string
    {
        $priceSum   = '0.00';
        $priceCount = 0;

        /** @var PriceInterface $price */
        foreach ($hardwarePrices as $price) {
            $priceDenormalized = $this->denormalizePrice($price);

            $priceSum = bcadd($priceSum, $priceDenormalized, $scale);
            ++$priceCount;
        }

        if ($priceCount < 1) {
            throw new RuntimeException('Unable to calculate V/B ratio, no price records.');
        }

        $priceCountAsString = (string) $priceCount;
        $priceAmountAverage = bcdiv($priceSum, $priceCountAsString, $scale);

        return $priceAmountAverage;
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
