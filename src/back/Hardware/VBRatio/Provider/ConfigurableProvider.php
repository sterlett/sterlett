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
use Sterlett\Hardware\VBRatio\CalculatorInterface;
use Sterlett\Hardware\VBRatio\ProviderInterface;
use Sterlett\Hardware\Price\ProviderInterface as PriceProviderInterface;
use Sterlett\Hardware\Benchmark\ProviderInterface as BenchmarkProviderInterface;
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
     * @param CalculatorInterface        $ratioCalculator   Performs V/B ratio calculation
     */
    public function __construct(
        PriceProviderInterface $priceProvider,
        BenchmarkProviderInterface $benchmarkProvider,
        CalculatorInterface $ratioCalculator
    ) {
        $this->priceProvider     = $priceProvider;
        $this->benchmarkProvider = $benchmarkProvider;
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

        $ratioListPromise = $sourceReadyPromise->then(
            function (array $sourceData) {
                // todo: try-catch

                [$priceList, $benchmarks] = $sourceData;

                // todo: link price/benchmark records by hardware names
                // todo: ratio calculation +expect possible exception

                return [];
            }
        );

        $ratioListPromise = $ratioListPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to fulfill the V/B ratio collection.', 0, $rejectionReason);
            }
        );

        return $ratioListPromise;
    }
}
