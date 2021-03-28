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
use Sterlett\Hardware\Price\SimpleAverageCalculator;
use Sterlett\Hardware\PriceInterface;
use Sterlett\Hardware\VBRatioInterface;
use Traversable;

/**
 * Converts a V/B ratio data from the object collection format to a raw string for HTTP handlers
 */
class Packer
{
    /**
     * Encapsulates logic for average amount calculation
     *
     * @var SimpleAverageCalculator
     */
    private SimpleAverageCalculator $priceCalculator;

    /**
     * Packer constructor.
     *
     * @param SimpleAverageCalculator $priceCalculator Encapsulates logic for average amount calculation
     */
    public function __construct(SimpleAverageCalculator $priceCalculator)
    {
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Returns a string, representing a "packed" ratio data (using required normalizers and encoders)
     *
     * @param Traversable<VBRatioInterface>|VBRatioInterface[] $ratios V/B ratio records
     *
     * @return string
     */
    public function packRatios(iterable $ratios): string
    {
        $ratioListPacked = [];

        foreach ($ratios as $ratio) {
            $ratioListPacked[] = $this->packRatio($ratio);
        }

        $ratioListEncoded = json_encode(['items' => $ratioListPacked]);

        return $ratioListEncoded;
    }

    /**
     * Transforms a single V/B ratio record to the API response format
     *
     * @param VBRatioInterface $ratio A V/B ratio record
     *
     * @return array
     */
    private function packRatio(VBRatioInterface $ratio): array
    {
        $sourceBenchmark   = $ratio->getSourceBenchmark();
        $hardwareName      = $sourceBenchmark->getHardwareName();
        $benchmarkValueInt = (int) $sourceBenchmark->getValue();

        $sourcePrices = $ratio->getSourcePrices();
        // reading a price sample to extract metadata for the whole set.
        $priceSample = $this->extractPriceSample($sourcePrices);

        $imageUri = $priceSample->getHardwareImage();

        $priceAverage  = (int) $this->priceCalculator->calculateAverage($sourcePrices, 0);
        $priceCurrency = $priceSample->getCurrency();

        $ratioValue = $ratio->getValue();

        $ratioPacked = [
            'name'       => $hardwareName,
            'image'      => $imageUri,
            'prices'     => [
                [
                    'type'      => 'average',
                    'value'     => $priceAverage,
                    'currency'  => $priceCurrency,
                    'precision' => 0,
                ],
            ],
            'vb_ratio'   => $ratioValue,
            'benchmarks' => [
                [
                    'name'  => 'PassMark',
                    'value' => $benchmarkValueInt,
                ],
            ],
        ];

        return $ratioPacked;
    }

    /**
     * Resolves and returns a "sample" price from the given set (will be used for metadata extraction)
     *
     * @param PriceInterface[] $prices An array of prices for the hardware item
     *
     * @return PriceInterface
     */
    private function extractPriceSample(array $prices): PriceInterface
    {
        $sampleKey = array_key_first($prices);

        if (null === $sampleKey) {
            throw new RuntimeException('Unable to extract a price sample: no price records (feeder).');
        }

        $priceSample = $prices[$sampleKey];

        return $priceSample;
    }
}
