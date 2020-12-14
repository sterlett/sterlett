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

namespace Sterlett\Tests\HardPrice\Price\Parser;

use PHPUnit\Framework\TestCase;
use Sterlett\HardPrice\Price\Parser\Tokenizer as PropertyTokenizer;
use Traversable;

/**
 * Tests price data recognizing from the HardPrice website
 */
final class TokenizerTest extends TestCase
{
    /**
     * Recognizes data parts to make properties for the price DTOs from the response message body
     *
     * @var PropertyTokenizer
     */
    private PropertyTokenizer $propertyTokenizer;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->propertyTokenizer = new PropertyTokenizer();
    }

    /**
     * Ensures seller identifiers and their hardware prices are properly tokenized
     *
     * @return void
     */
    public function testSellerIdentifiersAndHardwarePricesAreTokenized(): void
    {
        $responseBodyContents = file_get_contents(__DIR__ . '/../../../../_data/product_prices.raw');

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

        $priceListActual = $this->propertyTokenizer->tokenize($responseBodyContents);

        $sellerIdWithPriceArrayActual = [...$priceListActual];

        $this->assertEqualsCanonicalizing(
            $sellerIdWithPriceArrayExpected,
            $sellerIdWithPriceArrayActual,
            'Seller identifiers and their hardware prices are not properly tokenized.'
        );
    }
}
