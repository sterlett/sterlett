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

namespace Sterlett\Routine\Hardware\Benchmark;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use RuntimeException;
use Sterlett\Hardware\Benchmark\Retriever as BenchmarkRetriever;
use Sterlett\RoutineInterface;
use Throwable;

/**
 * Runs a background task, which actualizes benchmark information for hardware items
 */
class RetrievingRoutine implements RoutineInterface
{
    /**
     * Logs routine activity
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Event loop reference
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * Finds benchmark records for the different hardware items and saves them into the local storage
     *
     * @var BenchmarkRetriever
     */
    private BenchmarkRetriever $benchmarkRetriever;

    /**
     * The interval between benchmark retrieving attempts, in seconds (24h by default)
     *
     * @var float
     */
    private float $attemptInterval;

    /**
     * RetrievingRoutine constructor.
     *
     * @param LoggerInterface    $logger             Logs routine activity
     * @param LoopInterface      $loop               Event loop reference
     * @param BenchmarkRetriever $benchmarkRetriever Finds hardware benchmarks and saves them into the local storage
     * @param float              $attemptInterval    The interval between benchmark retrieving attempts, in seconds
     */
    public function __construct(
        LoggerInterface $logger,
        LoopInterface $loop,
        BenchmarkRetriever $benchmarkRetriever,
        float $attemptInterval = 86400.0
    ) {
        $this->logger             = $logger;
        $this->loop               = $loop;
        $this->benchmarkRetriever = $benchmarkRetriever;
        $this->attemptInterval    = $attemptInterval;
    }

    /**
     * {@inheritDoc}
     */
    public function run(): void
    {
        $this->loop->futureTick(fn () => $this->runInternal());
    }

    /**
     * Runs benchmark retrieving logic in the background task (as a timer)
     *
     * @return void
     */
    private function runInternal(): void
    {
        $this->logger->info('Executing a background task: benchmark retrieving.');

        try {
            $completionPromise = $this->benchmarkRetriever->retrieveBenchmarks();

            $completionPromise->then(
                function () {
                    $this->logger->info('Benchmark retrieving task is complete.');

                    // rescheduling, to perform a next attempt.
                    $this->loop->addTimer($this->attemptInterval, fn () => $this->runInternal());
                },
                // capturing & unwrapping the async stack trace, in case of error.
                function (Throwable $rejectionReason) {
                    $reasonNext = new RuntimeException('', 0, $rejectionReason);

                    while ($reasonNext = $reasonNext->getPrevious()) {
                        $exceptionMessage = $reasonNext->getMessage();

                        $this->logger->error($exceptionMessage);
                    }

                    $this->logger->critical('Benchmark retrieving task has failed.');

                    $this->loop->addTimer($this->attemptInterval, fn () => $this->runInternal());
                }
            );
        } catch (Throwable $exception) {
            // todo: enhance the context

            $this->logger->critical('Unable to schedule a background task: benchmark retrieving.');
        }
    }
}
