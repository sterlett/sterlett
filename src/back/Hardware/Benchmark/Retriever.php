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

namespace Sterlett\Hardware\Benchmark;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Dto\Hardware\Benchmark;
use Throwable;
use Traversable;

/**
 * Finds benchmark records for the different hardware items and saves them into the local storage
 */
class Retriever
{
    /**
     * Encapsulates the benchmark retrieving algorithm (async approach)
     *
     * @var ProviderInterface
     */
    private ProviderInterface $benchmarkProvider;

    /**
     * A storage with benchmark records
     *
     * @var Repository
     */
    private Repository $benchmarkRepository;

    /**
     * Retriever constructor.
     *
     * @param ProviderInterface $benchmarkProvider   Encapsulates the benchmark retrieving algorithm (async approach)
     * @param Repository        $benchmarkRepository A storage with benchmark records
     */
    public function __construct(ProviderInterface $benchmarkProvider, Repository $benchmarkRepository)
    {
        $this->benchmarkProvider   = $benchmarkProvider;
        $this->benchmarkRepository = $benchmarkRepository;
    }

    /**
     * Extracts hardware benchmarks from the specified provider and saves them using a repository reference. Returns a
     * promise that will be resolved when the benchmark retrieving process is complete (or errored).
     *
     * @return PromiseInterface<null>
     */
    public function retrieveBenchmarks(): PromiseInterface
    {
        $benchmarkListPromise = $this->benchmarkProvider->getBenchmarks();

        $benchmarkPersistencePromise = $benchmarkListPromise->then(
            function (iterable $benchmarks) {
                $this->persistBenchmarks($benchmarks);
            },
            function (Throwable $rejectionReason) {
                throw new RuntimeException(
                    'Unable to find and save hardware benchmarks (retriever).',
                    0,
                    $rejectionReason
                );
            }
        );

        // todo: truly guarantee a successful persist action for each benchmark record
        //  (MapReduce logic for database query promises is required)
        // it is not guaranteed for now (omitted).

        return $benchmarkPersistencePromise;
    }

    /**
     * Saves benchmark records into the local storage
     *
     * @param Traversable<Benchmark>|Benchmark[] $benchmarks A collection of benchmarks for a single hardware item
     *
     * @return void
     */
    private function persistBenchmarks(iterable $benchmarks): void
    {
        foreach ($benchmarks as $benchmark) {
            $this->benchmarkRepository->save($benchmark);
        }
    }
}
