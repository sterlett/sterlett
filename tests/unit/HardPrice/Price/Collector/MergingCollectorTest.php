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

namespace Sterlett\Tests\HardPrice\Price\Collector;

use PHPUnit\Framework\TestCase;
use Sterlett\HardPrice\Price\Collector\MergingCollector;
use Sterlett\HardPrice\Price\Collector\SequentialCollector;
use Sterlett\Hardware\PriceInterface;

/**
 * Tests merging price collector to ensure it aggregates price data by hardware identifiers correctly
 */
final class MergingCollectorTest extends TestCase
{
    /**
     * Makes a deterministic iterator for the hardware price collection
     *
     * @var MergingCollector
     */
    private MergingCollector $mergingCollector;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $priceMock = $this->createMock(PriceInterface::class);
        $priceMock
            ->method('getAmount')
            ->willReturn(13283)
        ;
        $priceMock
            ->method('getPrecision')
            ->willReturn(3)
        ;
        $priceMock
            ->method('getCurrency')
            ->willReturn('EUR')
        ;

        $sequentialCollectorMock = $this->createMock(SequentialCollector::class);
        $sequentialCollectorMock
            ->expects($this->once())
            ->method('makeIterator')
            ->withAnyParameters()
            ->willReturn(
                (function () use ($priceMock) {
                    yield 2533 => $priceMock;
                    yield 2533 => $priceMock;
                    yield 2533 => $priceMock;
                    yield 2700 => $priceMock;
                    yield 2900 => $priceMock;
                    yield 2900 => $priceMock;
                })()
            )
        ;

        $this->mergingCollector = new MergingCollector($sequentialCollectorMock);
    }

    /**
     * Ensures hardware prices are properly collected with merge logic
     *
     * @return void
     */
    public function testHardwarePricesAreCollectedWithMerge(): void
    {
        $hardwarePriceArrayExpected = [
            2533 => [
                '13,283 EUR',
                '13,283 EUR',
                '13,283 EUR',
            ],
            2700 => [
                '13,283 EUR',
            ],
            2900 => [
                '13,283 EUR',
                '13,283 EUR',
            ],
        ];

        $hardwarePricesActual = $this->mergingCollector->makeIterator([]);

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
            $pricesFormatted = [...$priceFormatter($hardwarePrices)];

            $hardwarePriceArrayActual[$hardwareIdentifier] = $pricesFormatted;
        }

        $this->assertEqualsCanonicalizing(
            $hardwarePriceArrayExpected,
            $hardwarePriceArrayActual,
            'Hardware prices are not properly collected (iterator logic).'
        );
    }
}
