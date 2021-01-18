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

use Sterlett\Dto\Hardware\VBRatio;
use Sterlett\Hardware\BenchmarkInterface;
use Sterlett\Hardware\PriceInterface;
use Sterlett\Hardware\VBRatioInterface;

/**
 * Creates relations for price records from resource A and independent benchmarks from resource B
 */
class SourceBinder
{
    /**
     * Finds matches between price records and benchmarks (by hardware name) and yields VBRatio object stubs for
     * further calculations.
     *
     * Price list is expected as Traversable<int, iterable>, where iterable element is Traversable<PriceInterface>
     * or PriceInterface[] (with optional int key as item identifier).
     *
     * A benchmark collection is expected as Traversable<BenchmarkInterface> or BenchmarkInterface[].
     *
     * @param iterable $hardwarePrices Hardware price records (resource A)
     * @param iterable $benchmarks     Benchmark results for the same hardware (resource B)
     *
     * @return iterable
     */
    public function bind(iterable $hardwarePrices, iterable $benchmarks): iterable
    {
        // todo: too much time, event loop's future tick queue should be used

        $time = -microtime(true);
        // holds data to fulfill successful matches with hardware price information.
        $priceBuffer = [];

        // an inverted index for all price records, to find better matches between price records and benchmarks (by
        // hardware name). Values are references to the data from buffer (numeric indexes).
        $priceInvertedIndex = [];

        // price indexing.
        foreach ($hardwarePrices as $itemIdentifier => $prices) {
            // downcasting from iterable (traversing a generator, if needed).
            $priceBuffer[$itemIdentifier] = [...$prices];

            $priceInvertedIndex = $this->updateIndex(
                $priceInvertedIndex,
                $priceBuffer[$itemIdentifier],
                $itemIdentifier
            );
        }
        $time = round(($time + microtime(true)) * 1000, 5) . ' ms';

        print_r($time);

        $ratioStubs = $this->traverseIndex($priceInvertedIndex, $priceBuffer, $benchmarks);

        yield from $ratioStubs;
    }

    /**
     * Registers a set of price records in the matching index
     *
     * @param array            $priceInvertedIndex A data structure (read-only pass) to connect benchmarks and prices
     * @param PriceInterface[] $prices             An array with hardware prices (downcasted from iterable)
     * @param int              $priceBufferIndex   Index, where price records are stored in the in-memory buffer
     *
     * @return array
     */
    private function updateIndex(array $priceInvertedIndex, array $prices, int $priceBufferIndex): array
    {
        $itemName = $this->extractPriceItemName($prices);
        // normalizing item name for better results.
        $itemNameNormalized = $this->normalizeItemName($itemName);

        $itemNameParts = explode(' ', $itemNameNormalized);
        // removing duplicate words.
        $itemNameParts = array_keys(array_flip($itemNameParts));

        foreach ($itemNameParts as $word) {
            $wordNormalized = $this->normalizeWord($word);

            if ('' === $wordNormalized) {
                continue;
            }

            if (!array_key_exists($wordNormalized, $priceInvertedIndex)) {
                $priceInvertedIndex[$wordNormalized] = [$priceBufferIndex];

                continue;
            }

            $priceInvertedIndex[$wordNormalized][] = $priceBufferIndex;
        }

        return $priceInvertedIndex;
    }

    /**
     * Yields stubs for V/B ratio calculation results, with source prices and benchmarks, which were connected to each
     * other by the hardware name using inverted in-memory index
     *
     * @param array    $priceInvertedIndex A data structure (read-only pass) to connect benchmarks and prices
     * @param array    $priceBuffer        An array with hardware prices (read-only pass)
     * @param iterable $benchmarks         The benchmark results collection
     *
     * @return iterable
     */
    private function traverseIndex(array $priceInvertedIndex, array $priceBuffer, iterable $benchmarks): iterable
    {
        foreach ($benchmarks as $benchmark) {
            $itemName = $this->extractBenchmarkItemName($benchmark);

            $itemNameNormalized = $this->normalizeItemName($itemName);
            // consider hardware items from benchmark results as OEM versions for price matching.
            // OEM will have more priority, than the BOX variants of the same chip.
            if (false === mb_stripos($itemNameNormalized, 'oem')) {
                $itemNameNormalized .= ' oem';
            }

            $betterMatchIndex = $this->findBetterMatch($priceInvertedIndex, $itemNameNormalized);

            if (null === $betterMatchIndex) {
                continue;
            }

            $ratioStub = $this->makeRatioStub($benchmark, $priceBuffer, $betterMatchIndex);

            yield $ratioStub;
        }
    }

    /**
     * Returns a numeric index for the price buffer, which indicates the highest possible match between the chosen
     * collection of price records (under that index in the buffer) and an item from the benchmark results
     *
     * @param array  $priceInvertedIndex A data structure (read-only pass) to connect benchmarks and prices
     * @param string $itemName           Hardware item name from the benchmark results, to find related price records
     *
     * @return int|null
     */
    private function findBetterMatch(array $priceInvertedIndex, string $itemName): ?int
    {
        $connectionMap = $this->buildConnectionMap($priceInvertedIndex, $itemName);
        asort($connectionMap, SORT_NUMERIC);

        // first position, where will be at least 1 non-stop word will be considered as an actual match.
        // todo

        $betterMatchIndex = array_key_first($connectionMap);

        return $betterMatchIndex;
    }

    /**
     * Returns a special data structure, which will be used for relation analysis
     *
     * @param array  $priceInvertedIndex A data structure (read-only pass) to connect benchmarks and prices
     * @param string $itemName           Hardware item name from the benchmark results, to find related price records
     *
     * @return array
     */
    private function buildConnectionMap(array $priceInvertedIndex, string $itemName): array
    {
        $itemNameParts = explode(' ', $itemName);
        $connectionMap = [];

        foreach ($itemNameParts as $word) {
            $wordNormalized = $this->normalizeWord($word);

            if ('' === $wordNormalized || !array_key_exists($wordNormalized, $priceInvertedIndex)) {
                continue;
            }

            $isStopWord = $this->isStopWord($wordNormalized);
            $wordWeight = ((int) $isStopWord << 10) + 1; // can give some performance boost (no branch guessing)
            // transcription: $wordWeight += !$isStopWord ? 1025 : 1;

            $priceBufferIndexes = $priceInvertedIndex[$wordNormalized];

            foreach ($priceBufferIndexes as $priceBufferIndex) {
                $indexWeight = $connectionMap[$priceBufferIndex] ?? 0;
                $indexWeight += $wordWeight;

                $connectionMap[$priceBufferIndex] = $indexWeight;
            }
        }

        return $connectionMap;
    }

    private function isStopWord(string $word): bool
    {
        // todo

        return false;
    }

    /**
     * Builds and returns a stub for the V/B ratio calculation result
     *
     * @param BenchmarkInterface $benchmark        The benchmark results object
     * @param array              $priceBuffer      A buffer with price records, to make ratio stubs
     * @param int                $priceBufferIndex Index in the price buffer, that has been chosen as the better
     *                                             possible relation to the given benchmark object (by item name)
     *
     * @return VBRatioInterface
     */
    private function makeRatioStub(
        BenchmarkInterface $benchmark,
        array $priceBuffer,
        int $priceBufferIndex
    ): VBRatioInterface {
        $ratioStub = new VBRatio();
        $ratioStub->setSourceBenchmark($benchmark);

        foreach ($priceBuffer[$priceBufferIndex] as $price) {
            $ratioStub->addSourcePrice($price);
        }

        return $ratioStub;
    }

    /**
     * Returns item name that has been extracted from the collection of hardware prices
     *
     * @param PriceInterface[] $prices A ready-only array of hardware prices (downcasted from iterable)
     *
     * @return string
     */
    private function extractPriceItemName(array $prices): string
    {
        // todo: safer [0]
        $priceToAnalyse = $prices[0];
        $itemName       = $priceToAnalyse->getHardwareName();

        return $itemName;
    }

    /**
     * Returns item name that has been extracted from the benchmark DTO
     *
     * @param BenchmarkInterface $benchmark A benchmark results object
     *
     * @return string
     */
    private function extractBenchmarkItemName(BenchmarkInterface $benchmark): string
    {
        $itemName = $benchmark->getHardwareName();

        return $itemName;
    }

    /**
     * Returns a normalized item name, according to the matching algorithm rules
     *
     * @param string $itemName Hardware item name
     *
     * @return string
     */
    private function normalizeItemName(string $itemName): string
    {
        $itemNameNormalized = preg_replace('/[-()]/', ' ', $itemName);

        return $itemNameNormalized;
    }

    /**
     * Returns a normalized part of the hardware item name
     *
     * @param mixed $word A part of hardware item name (maybe an int or a string, for the most cases)
     *
     * @return string
     */
    private function normalizeWord($word): string
    {
        $wordAsString   = (string) $word;
        $wordNormalized = mb_strtolower(trim($wordAsString));

        return $wordNormalized;
    }
}
