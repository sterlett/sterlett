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

namespace Sterlett\Hardware\VBRatio\Provider;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Dto\Hardware\VBRatio;
use Sterlett\Hardware\Benchmark\ProviderInterface as BenchmarkProviderInterface;
use Sterlett\Hardware\Price\ProviderInterface as PriceProviderInterface;
use Sterlett\Hardware\VBRatio\CalculatorInterface;
use Sterlett\Hardware\VBRatio\ProviderInterface;
use Sterlett\Hardware\VBRatio\SourceBinder;
use Throwable;
use function React\Promise\all;

/**
 * This provider is responsible for V/B rating calculation, to measure hardware efficiency and consumer appeal.
 *
 * It uses other data providers, such as price/benchmark providers and also a separate service that encapsulates
 * implementation for the actual calculation algorithm.
 */
class ConfigurableProvider implements ProviderInterface
{
    /**
     * Retrieves a collection of hardware prices
     *
     * @var PriceProviderInterface
     */
    private PriceProviderInterface $priceProvider;

    /**
     * Retrieves benchmark results for the hardware items
     *
     * @var BenchmarkProviderInterface
     */
    private BenchmarkProviderInterface $benchmarkProvider;

    /**
     * Creates relations for price records and benchmarks
     *
     * @var SourceBinder
     */
    private SourceBinder $sourceBinder;

    /**
     * Performs V/B ratio calculation
     *
     * @var CalculatorInterface
     */
    private CalculatorInterface $ratioCalculator;

    /**
     * ConfigurableProvider constructor.
     *
     * @param PriceProviderInterface     $priceProvider     Retrieves a collection of hardware prices
     * @param BenchmarkProviderInterface $benchmarkProvider Retrieves benchmark results for the hardware items
     * @param SourceBinder               $sourceBinder      Creates relations for price records and benchmarks
     * @param CalculatorInterface        $ratioCalculator   Performs V/B ratio calculation
     */
    public function __construct(
        PriceProviderInterface $priceProvider,
        BenchmarkProviderInterface $benchmarkProvider,
        SourceBinder $sourceBinder,
        CalculatorInterface $ratioCalculator
    ) {
        $this->priceProvider     = $priceProvider;
        $this->benchmarkProvider = $benchmarkProvider;
        $this->sourceBinder      = $sourceBinder;
        $this->ratioCalculator   = $ratioCalculator;
    }

    /**
     * {@inheritDoc}
     */
    public function getRatios(): PromiseInterface
    {
        $priceListPromise     = $this->priceProvider->getPrices();
        $benchmarkListPromise = $this->benchmarkProvider->getBenchmarks();

        $sourceReadyPromise = all([$priceListPromise, $benchmarkListPromise]);

        $ratioListPromise = $sourceReadyPromise
            ->then(
                function (array $sourceData) {
                    [$priceList, $benchmarks] = $sourceData;

                    $ratioStubs = $this->sourceBinder->bind($priceList, $benchmarks);

                    return $ratioStubs;
                }
            )
            ->then(fn (iterable $ratioStubs) => $this->fulfillRatioStubs($ratioStubs))
        ;

        $ratioListPromise = $ratioListPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to fulfill the V/B ratio collection.', 0, $rejectionReason);
            }
        );

        return $ratioListPromise;
    }

    private function fulfillRatioStubs(iterable $ratioStubs): iterable
    {
        /** @var VBRatio $ratio */
        foreach ($ratioStubs as $ratio) {
            $sourcePrices = $ratio->getSourcePrices();

            $sourceBenchmark = $ratio->getSourceBenchmark();
            $benchmarkValue  = $sourceBenchmark->getValue();

            // todo: +expect possible exception
            $ratioValue = $this->ratioCalculator->calculateRatio($sourcePrices, $benchmarkValue);
            $ratioValueAsFloat = (float) $ratioValue;

            $ratio->setValue($ratioValueAsFloat);

            yield $ratio;
        }
    }
}
