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

namespace Sterlett\Tests\Hardware\VBRatio;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\StreamSelectLoop;
use Sterlett\Dto\Hardware\Benchmark;
use Sterlett\Dto\Hardware\Price;
use Sterlett\Hardware\VBRatio\SourceBinder;
use Throwable;
use function Clue\React\Block\await;

/**
 * A test for the service that connects benchmarks and price records (from different sources)
 */
class SourceBinderTest extends TestCase
{
    /**
     * Event loop
     *
     * @var StreamSelectLoop
     */
    private StreamSelectLoop $loop;

    /**
     * Creates relations for price records from resource A and independent benchmarks from resource B
     *
     * @var SourceBinder
     */
    private SourceBinder $sourceBinder;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $loggerStub = $this->createStub(LoggerInterface::class);
        $this->loop = new StreamSelectLoop();

        $this->sourceBinder = new SourceBinder($loggerStub, $this->loop);
    }

    /**
     * Tests the base source binder's logic
     *
     * Matching rules:
     * - Equal model number = the most accurate case
     * - Details match (e.g. OEM/BOX)
     * - Shorter string - better, for border cases (overall % match priority)
     *
     * @return void
     */
    public function testBenchmarksAndPricesAreBinded(): void
    {
        $price1 = new Price();
        $price1->setHardwareName('Процессор AMD RYZEN 9 3900 BOX');
        $price1->setHardwareImage('http://lorempixel.com/800/600/cats/');
        $price1->setSellerIdentifier('testSeller1');
        $price1->setAmount(36110);
        $price1->setPrecision(0);
        $price1->setCurrency('RUB');

        $price2 = new Price();
        $price2->setHardwareName('9 RyZeN процессор model 3900 (OEM-version, AM4 Matisse) amd');
        $price2->setHardwareImage('http://lorempixel.com/800/600/cats/');
        $price2->setSellerIdentifier('testSeller2');
        $price2->setAmount(35110);
        $price2->setPrecision(0);
        $price2->setCurrency('RUB');

        $price3 = new Price();
        $price3->setHardwareName('Processor by AMD RYZEN 9 PRO1 SUPER2 MEGA3 3900 OEM AM4 Matisse');
        $price3->setHardwareImage('http://lorempixel.com/800/600/cats/');
        $price3->setSellerIdentifier('testSeller3');
        $price3->setAmount(36110);
        $price3->setPrecision(0);
        $price3->setCurrency('RUB');

        $priceRecords = [
            1 => [$price1],
            2 => [$price2],
            3 => [$price3],
        ];

        $benchmark1 = new Benchmark();
        $benchmark1->setHardwareName('AMD Ryzen 9 3900');
        $benchmark1->setValue('30906');

        $benchmark2 = new Benchmark();
        $benchmark2->setHardwareName('AMD Ryzen 9 3900X');
        $benchmark2->setValue('31800');

        $benchmarks = [
            $benchmark1,
            $benchmark2,
        ];

        $ratioStubListPromise = $this->sourceBinder->bind($priceRecords, $benchmarks);

        try {
            /** @var iterable $ratioStubs */
            $ratioStubs = await($ratioStubListPromise, $this->loop, 5.0);
        } catch (Throwable $rejectionReason) {
            $failReasonMessage = sprintf(
                'A ratio stub list promise has been rejected with a reason: %s',
                (string) $rejectionReason
            );

            $this->fail($failReasonMessage);
        }

        $ratioStubArrayActual = [...$ratioStubs];

        $dataExpected = [
            ['AMD Ryzen 9 3900', '9 RyZeN процессор model 3900 (OEM-version, AM4 Matisse) amd'],
        ];

        $this->assertEquals($dataExpected[0][0], $ratioStubArrayActual[0]->getSourceBenchmark()->getHardwareName(), 'Incorrect binding.');
        $this->assertEquals($dataExpected[0][1], $ratioStubArrayActual[0]->getSourcePrices()[0]->getHardwareName(), 'Incorrect binding.');
    }
}
