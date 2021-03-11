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

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventDispatcher;
use Sterlett\Event\Listener\CpuMarkAcceptanceListener;
use Sterlett\Event\VBRatiosEmittedEvent;
use Sterlett\Hardware\Price\SimpleAverageCalculator;
use Sterlett\Hardware\PriceInterface;
use Sterlett\Hardware\VBRatioInterface;
use Throwable;
use Traversable;

/**
 * Emits a list with V/B ranks for the available hardware items (using a configured provider).
 *
 * It serves data actualization for the async HTTP handlers.
 *
 * @see CpuMarkAcceptanceListener
 */
class Feeder
{
    /**
     * Provides V/B ratio lists
     *
     * @var ProviderInterface
     */
    private ProviderInterface $ratioProvider;

    /**
     * Encapsulates logic for average amount calculation
     *
     * @var SimpleAverageCalculator
     */
    private SimpleAverageCalculator $priceCalculator;

    /**
     * Provides hooks on domain-specific lifecycles by dispatching events
     *
     * @var DeferredEventDispatcher
     */
    private DeferredEventDispatcher $eventDispatcher;

    /**
     * Feeder constructor.
     *
     * @param ProviderInterface       $ratioProvider   Provides V/B ratio lists
     * @param SimpleAverageCalculator $priceCalculator Encapsulates logic for average amount calculation
     * @param DeferredEventDispatcher $eventDispatcher Provides hooks on domain-specific lifecycles
     */
    public function __construct(
        ProviderInterface $ratioProvider,
        SimpleAverageCalculator $priceCalculator,
        DeferredEventDispatcher $eventDispatcher
    ) {
        $this->ratioProvider   = $ratioProvider;
        $this->priceCalculator = $priceCalculator;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns a promise that will be resolved when the V/B ratio list "feed" operation is complete
     *
     * @return PromiseInterface<null>
     */
    public function emitRatios(): PromiseInterface
    {
        $ratioListTransferPromise = $this->ratioProvider
            // calling in a responsible provider.
            ->getRatios()
            // transferring data to the async handlers for distribution via HTTP.
            ->then(fn (iterable $ratios) => $this->transferRatios($ratios))
        ;

        $ratioListTransferPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to dispatch a V/B ratio list (feeder).', 0, $rejectionReason);
            }
        );

        return $ratioListTransferPromise;
    }

    /**
     * Calls a dispatcher for transferring V/B ratio dataset to the async handlers
     *
     * @param Traversable<VBRatioInterface>|VBRatioInterface[] $ratios V/B ratio records
     *
     * @return PromiseInterface<null>
     */
    private function transferRatios(iterable $ratios): PromiseInterface
    {
        $ratioListPacked = [];

        foreach ($ratios as $ratio) {
            $ratioListPacked[] = $this->packRatio($ratio);
        }

        $ratioListEncoded = json_encode(['items' => $ratioListPacked]);

        $ratioListEmittedEvent = new VBRatiosEmittedEvent();
        $ratioListEmittedEvent->setRatioData($ratioListEncoded);

        $this->eventDispatcher->dispatch($ratioListEmittedEvent, VBRatiosEmittedEvent::NAME);

        $eventPropagationPromise = $ratioListEmittedEvent->getPromise();

        return $eventPropagationPromise;
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
