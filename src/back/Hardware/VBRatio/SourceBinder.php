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

use ArrayIterator;
use Iterator;
use IteratorIterator;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Dto\Hardware\VBRatio;
use Sterlett\Hardware\BenchmarkInterface;
use Sterlett\Hardware\Price\InvertedIndex;
use Sterlett\Hardware\PriceInterface;
use Sterlett\Hardware\VBRatioInterface;
use Throwable;
use Traversable;

/**
 * Creates relations for price records from resource A and independent benchmarks from resource B
 *
 * todo: extract price indexing logic into a separate service
 */
class SourceBinder
{
    /**
     * Performs event logging for the source binder
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * A loop reference, to perform in non-blocking mode
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * SourceBinder constructor.
     *
     * @param LoggerInterface $logger Performs event logging for the source binder
     * @param LoopInterface   $loop   A loop reference, to perform in non-blocking mode
     */
    public function __construct(LoggerInterface $logger, LoopInterface $loop)
    {
        $this->logger = $logger;
        $this->loop   = $loop;
    }

    /**
     * Finds matches between price records and benchmarks (by hardware name) and then yields VBRatio object stubs for
     * further calculations. Returns a promise that will be resolved into collection of V/B ratio stub objects.
     *
     * Price list is expected as Traversable<int, iterable>, where iterable element is Traversable<PriceInterface>
     * or PriceInterface[] (with optional int key as item identifier).
     *
     * A benchmark collection is expected as Traversable<BenchmarkInterface> or BenchmarkInterface[].
     *
     * The resulting value is Traversable<VBRatioInterface>.
     *
     * @param iterable $hardwarePrices Hardware price records (resource A)
     * @param iterable $benchmarks     Benchmark results for the same hardware (resource B)
     *
     * @return PromiseInterface<iterable>
     */
    public function bind(iterable $hardwarePrices, iterable $benchmarks): PromiseInterface
    {
        $indexReadyPromise = $this->buildPriceIndex($hardwarePrices);

        $timeIndexingStarted  = -microtime(true);
        $ratioStubListPromise = $indexReadyPromise->then(
            function (array $indexContext) use ($timeIndexingStarted, $benchmarks) {
                $timeIndexingElapsed = round(($timeIndexingStarted + microtime(true)) * 1000, 2);
                $this->logger->debug(
                    'Data binder: price indexing is complete ({time} ms).',
                    [
                        'time' => $timeIndexingElapsed,
                    ]
                );

                [$priceIndex, $priceBuffer] = $indexContext;

                $ratioStubs = $this->traverseIndex($priceIndex, $priceBuffer, $benchmarks);

                return $ratioStubs;
            },
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to build a price index (source binder).', 0, $rejectionReason);
            }
        );

        return $ratioStubListPromise;
    }

    /**
     * Returns a promise that will be resolved when the price index building is complete
     *
     * @param iterable $hardwarePrices Price records
     *
     * @return PromiseInterface<null>
     */
    private function buildPriceIndex(iterable $hardwarePrices): PromiseInterface
    {
        $indexingDeferred = new Deferred();

        // an inverted index for all price records, to find better matches between price records and benchmarks (by
        // hardware name). Values are references to the data from buffer (numeric indexes).
        $priceIndex = new InvertedIndex();

        // holds data to fulfill successful matches with hardware price information.
        $priceBuffer = [];

        if ($hardwarePrices instanceof Traversable) {
            $priceIterator = new IteratorIterator($hardwarePrices);
        } else {
            $priceIterator = new ArrayIterator($hardwarePrices);
        }

        $priceIterator->rewind();

        // scheduling recursive and async price indexing.
        $this->loop->futureTick(
            fn () => $this->doIndexIteration($indexingDeferred, $priceIterator, $priceIndex, $priceBuffer)
        );

        $indexReadyPromise = $indexingDeferred->promise();

        return $indexReadyPromise;
    }

    /**
     * Represents a single index building iteration, which will be executed as a separate tick in the event loop queue
     *
     * @param Deferred      $indexingDeferred Represents the indexing process itself (for promise resolving)
     * @param Iterator      $priceIterator    Gives access to the price collection
     * @param InvertedIndex $priceIndex       The resulting price index
     * @param array&        $priceBuffer      [ref] The buffer for accumulated price data
     *
     * @return void
     */
    private function doIndexIteration(
        Deferred $indexingDeferred,
        Iterator $priceIterator,
        InvertedIndex $priceIndex,
        array &$priceBuffer
    ): void {
        // if no more price records for the index - resolving the promise (index building is complete).
        if (!$priceIterator->valid()) {
            $indexingDeferred->resolve([$priceIndex, $priceBuffer]);

            return;
        }

        try {
            $prices         = $priceIterator->current();
            $itemIdentifier = $priceIterator->key();

            // downcasting from iterable (traversing a generator, if needed).
            $priceBuffer[$itemIdentifier] = [...$prices];

            $this->updateIndex($priceIndex, $priceBuffer[$itemIdentifier], $itemIdentifier);
        } catch (Throwable $exception) {
            $this->logger->error(
                'An error has been occurred during price indexing (source binder).',
                [
                    'exception' => $exception,
                ]
            );
        } finally {
            $priceIterator->next();

            // scheduling next iteration.
            $this->loop->futureTick(
                fn () => $this->doIndexIteration($indexingDeferred, $priceIterator, $priceIndex, $priceBuffer)
            );
        }
    }

    /**
     * Registers a set of price records in the matching index
     *
     * @param InvertedIndex    $priceIndex       A data structure to connect benchmarks and prices
     * @param PriceInterface[] $prices           An array with hardware prices (downcasted from iterable)
     * @param int              $priceBufferIndex Index, where price records are stored in the in-memory buffer
     *
     * @return void
     */
    private function updateIndex(InvertedIndex $priceIndex, array $prices, int $priceBufferIndex): void
    {
        $itemName = $this->extractPriceItemName($prices);
        // reading amount of characters in the string, to determine additional index priority within set.
        $itemNameLen = mb_strlen($itemName);

        // normalizing item name for better results.
        $itemNameNormalized = $this->normalizeItemName($itemName);

        $itemNameParts = explode(' ', $itemNameNormalized);

        foreach ($itemNameParts as $word) {
            $wordNormalized = $this->normalizeWord($word);

            if ('' === $wordNormalized) {
                continue;
            }

            $priceIndex->add($wordNormalized, $priceBufferIndex, $itemNameLen);
        }
    }

    /**
     * Yields stubs for V/B ratio calculation results, with source prices and benchmarks, which were connected to each
     * other by the hardware name using inverted in-memory index
     *
     * @param InvertedIndex $priceIndex  A data structure to connect benchmarks and prices
     * @param array         $priceBuffer An array with hardware prices (read-only pass)
     * @param iterable      $benchmarks  The benchmark results collection
     *
     * @return iterable
     */
    private function traverseIndex(InvertedIndex $priceIndex, array $priceBuffer, iterable $benchmarks): iterable
    {
        foreach ($benchmarks as $benchmark) {
            $itemName = $this->extractBenchmarkItemName($benchmark);

            $itemNameNormalized = $this->normalizeItemName($itemName);
            // consider hardware items from benchmark results as OEM versions for price matching.
            // OEM will have more priority, than the BOX variants of the same chip.
            if (false === mb_stripos($itemNameNormalized, 'oem')) {
                $itemNameNormalized .= ' oem';
            }

            $betterMatchIndex = $this->findBetterMatch($priceIndex, $itemNameNormalized);

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
     * @param InvertedIndex $priceIndex A data structure to connect benchmarks and prices
     * @param string        $itemName   Hardware item name from the benchmark results, to find related price records
     *
     * @return int|null
     */
    private function findBetterMatch(InvertedIndex $priceIndex, string $itemName): ?int
    {
        $connectionMap = $this->buildConnectionMap($priceIndex, $itemName);
        // sorting (ASC); the most relevant price records for the hardware item will be at the end.
        // in a border case, when there are two equal scores, the later ADDED index will have more priority.
        asort($connectionMap, SORT_NUMERIC);

        $weightSubtractReserve = 1 << 16;
        // first position, where will be at least 1 non-stop word will be considered as an actual match.
        // transcription: a stop word (e.g. "i7", "ryzen") cost 1024 points, a unique one (e.g. "3700XT") - 1049600.
        // to ensure that the relation between benchmark result and price record is correct, the item name from the
        // price record MUST score at least 1025 points (it would mean they have a unique model number in common).
        // Example:
        //          Intel    Core    i9    10850K    BOX    Comet    Lake
        //          ^        ^       ^     ^         ^      ^        ^
        //          |1024    |1024   |1024 |1049600  |1024  |1024    |1024    = 1055744 (total points for 100% match)
        //         stop     stop    stop   unique   stop   stop     stop
        // count of the stop words is also matters, for example, OEM (or "tray") and BOX (or "boxed") variants have
        // different price tags and, generally, the OEM version is cheaper (and, therefore, will be more relevant
        // in the context of benchmarking).
        $weightThreshold = (1 << 20) + (1 << 10) - $weightSubtractReserve;

        $bufferIndex = array_key_last($connectionMap);

        // no match at all.
        if (null === $bufferIndex) {
            return null;
        }

        $indexWeight = $connectionMap[$bufferIndex];

        // no concrete model match.
        if ($indexWeight < $weightThreshold) {
            return null;
        }

        // removing index to prevent duplicate matches.
        $priceIndex->removeIndex($bufferIndex);

        return $bufferIndex;
    }

    /**
     * Returns a special data structure, which will be used for relation analysis
     *
     * @param InvertedIndex $priceIndex A data structure to connect benchmarks and prices
     * @param string        $itemName   Hardware item name from the benchmark results, to find related price records
     *
     * @return array
     */
    private function buildConnectionMap(InvertedIndex $priceIndex, string $itemName): array
    {
        $itemNameParts = explode(' ', $itemName);
        $connectionMap = [];

        foreach ($itemNameParts as $word) {
            $wordNormalized = $this->normalizeWord($word);
            $indexEntries   = $priceIndex->get($wordNormalized);

            if (null === $indexEntries) {
                continue;
            }

            $isStopWord = $this->isStopWord($wordNormalized);
            $wordWeight = ((int) !$isStopWord << 20) + (1 << 10); // to remove branch guessing
            // transcription: $wordWeight = !$isStopWord ? 1049600 : 1024;

            foreach ($indexEntries as $indexEntry) {
                $priceBufferIndex = $indexEntry->index;
                // subtract handles border cases, when index scores are equal, the lesser source string will be
                // preferable (considering as a more accurate match).
                $weightSubtract = $indexEntry->priority;

                $indexWeight = $connectionMap[$priceBufferIndex] ?? 0;
                $indexWeight += $wordWeight - $weightSubtract;

                $connectionMap[$priceBufferIndex] = $indexWeight;
            }
        }

        return $connectionMap;
    }

    /**
     * Returns positive whenever a given word is a non-stop word, in the context of hardware items (models).
     *
     * Stop word example: amd, intel, i7, ryzen, 9, v4, 3.50ghz
     * Model number example: 8124M, 3800XT, W3680, V1756B
     *
     * @param string $word A minimal unit (part) of the item name
     *
     * @return bool
     */
    private function isStopWord(string $word): bool
    {
        // discarding false-positive model number matches.
        if (1 === preg_match('/ghz|^[0-9]$|^[a-z][0-9]{1,2}$/iU', $word)) {
            return true;
        }

        // trying to match a model pattern.
        if (1 === preg_match('/^[a-z]*[0-9]+[a-z]*$/iU', $word)) {
            return false;
        }

        return true;
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

        $hardwarePrices = $priceBuffer[$priceBufferIndex];

        foreach ($hardwarePrices as $price) {
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
     * @param mixed $word A part of hardware item name (may be an int or a string, for the most cases)
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
