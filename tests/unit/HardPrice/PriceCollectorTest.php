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

namespace Sterlett\Tests\HardPrice;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Sterlett\HardPrice\Parser\PriceParser;
use Sterlett\HardPrice\PriceCollector;
use Sterlett\Hardware\PriceInterface;

/**
 * Tests price collecting stage logic (transforming from the raw responses to the price DTO list)
 */
final class PriceCollectorTest extends TestCase
{
    /**
     * Collects price responses and builds an iterator to access price data, keyed by the specific hardware identifiers
     *
     * @var PriceCollector
     */
    private PriceCollector $priceCollector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $priceMock = $this->createMock(PriceInterface::class);
        $priceMock
            ->method('getAmount')
            ->willReturn(15554)
        ;
        $priceMock
            ->method('getPrecision')
            ->willReturn(2)
        ;
        $priceMock
            ->method('getCurrency')
            ->willReturn('USD')
        ;

        $priceParserMock = $this->createMock(PriceParser::class);
        $priceParserMock
            ->expects($this->atLeastOnce())
            ->method('parse')
            ->withAnyParameters()
            ->willReturn([$priceMock])
        ;

        $this->priceCollector = new PriceCollector($priceParserMock);
    }

    /**
     * Ensures hardware prices are properly collected using a specified price parser and generator's logic
     *
     * @return void
     */
    public function testHardwarePricesAreCollected(): void
    {
        $responseMock = $this->createMock(ResponseInterface::class);

        $responseListById = [
            2533 => [
                $responseMock,
                $responseMock,
                $responseMock,
            ],
            2900 => [
                $responseMock,
            ],
        ];

        $hardwarePriceArrayExpected = [
            2533 => [
                '155,54 USD',
                '155,54 USD',
                '155,54 USD',
            ],
            2900 => [
                '155,54 USD',
            ],
        ];

        $hardwarePricesActual = $this->priceCollector->makeIterator($responseListById);

        $priceFormatter = function (iterable $prices) {
            /** @var PriceInterface $price */
            foreach ($prices as $price) {
                $priceAmount    = $price->getAmount();
                $pricePrecision = $price->getPrecision();
                $priceCurrency  = $price->getCurrency();

                $priceFormatted = substr_replace($priceAmount, ',', -$pricePrecision, 0) . ' ' . $priceCurrency;

                yield $priceFormatted;
            }
        };

        $hardwarePriceArrayActual = [];

        foreach ($hardwarePricesActual as $hardwareIdentifier => $hardwarePrices) {
            $pricesFormatted = iterator_to_array($priceFormatter($hardwarePrices), false);

            if (!array_key_exists($hardwareIdentifier, $hardwarePriceArrayActual)) {
                $hardwarePriceArrayActual[$hardwareIdentifier] = $pricesFormatted;

                continue;
            }

            $hardwarePriceArrayActual[$hardwareIdentifier] = array_merge(
                $hardwarePriceArrayActual[$hardwareIdentifier],
                $pricesFormatted
            );
        }

        $this->assertEqualsCanonicalizing(
            $hardwarePriceArrayExpected,
            $hardwarePriceArrayActual,
            'Hardware prices are not properly collected (iterator logic).'
        );
    }
}
