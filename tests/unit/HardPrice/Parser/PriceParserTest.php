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

namespace Sterlett\Tests\HardPrice\Parser;

use PHPUnit\Framework\TestCase;
use Sterlett\HardPrice\Parser\PriceParser;
use Traversable;

/**
 * Tests price data parsing from HardPrice website
 */
final class PriceParserTest extends TestCase
{
    /**
     * Transforms price data from the raw format to the list of application-level DTOs
     *
     * @var PriceParser
     */
    private PriceParser $priceParser;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->priceParser = new PriceParser();
    }

    /**
     * Ensures seller identifiers and their hardware prices are properly parsed
     *
     * @return void
     */
    public function testSellerIdentifiersAndHardwarePricesAreParsed(): void
    {
        $responseBodyContents = file_get_contents(__DIR__ . '/../../../_data/product_prices.raw');

        $sellerIdWithPriceArrayExpected = [
            [1, 19850],
            [3, 18290],
            [4, 16489],
            [5, 14043],
            [6, 18299],
            [32, 17160],
            [45, 16583],
            [63, 7040],
        ];

        $priceListActual = $this->priceParser->parse($responseBodyContents);

        if ($priceListActual instanceof Traversable) {
            $sellerIdWithPriceArrayActual = iterator_to_array($priceListActual);
        } else {
            $sellerIdWithPriceArrayActual = (array) $priceListActual;
        }

        $this->assertEqualsCanonicalizing(
            $sellerIdWithPriceArrayExpected,
            $sellerIdWithPriceArrayActual,
            'Seller identifiers and their hardware prices are not properly parsed.'
        );
    }
}
