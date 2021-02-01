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

namespace Sterlett\Routine\Hardware\Price;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Sterlett\Hardware\Price\Retriever as PriceRetriever;
use Sterlett\RoutineInterface;
use Throwable;

/**
 * Runs a background task, which actualizes price information for hardware items
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
     * Finds price records for the different hardware items and saves them into the local storage
     *
     * @var PriceRetriever
     */
    private PriceRetriever $priceRetriever;

    /**
     * The interval between price retrieving attempts, in seconds (8h by default)
     *
     * @var float
     */
    private float $attemptInterval;

    /**
     * RetrievingRoutine constructor.
     *
     * @param LoggerInterface $logger          Logs routine activity
     * @param LoopInterface   $loop            Event loop reference
     * @param PriceRetriever  $priceRetriever  Finds hardware prices and saves them into the local storage
     * @param float           $attemptInterval The interval between price retrieving attempts, in seconds
     */
    public function __construct(
        LoggerInterface $logger,
        LoopInterface $loop,
        PriceRetriever $priceRetriever,
        float $attemptInterval = 28800.0
    ) {
        $this->logger          = $logger;
        $this->loop            = $loop;
        $this->priceRetriever  = $priceRetriever;
        $this->attemptInterval = $attemptInterval;
    }

    /**
     * {@inheritDoc}
     */
    public function run(): void
    {
        $this->loop->futureTick(fn () => $this->runInternal());
    }

    /**
     * Runs price retrieving logic in the background task (as a timer)
     *
     * @return void
     */
    private function runInternal(): void
    {
        $this->logger->info('Executing a background task: price retrieving.');

        try {
            $completionPromise = $this->priceRetriever->retrievePrices();

            $completionPromise->then(
                function () {
                    $this->logger->info('Price retrieving task is complete.');

                    // rescheduling, to perform a next attempt.
                    $this->loop->addTimer($this->attemptInterval, fn () => $this->runInternal());
                },
                function (Throwable $rejectionReason) {
                    $this->logger->error('Price retrieving task has failed.', ['reason' => $rejectionReason]);

                    $this->loop->addTimer($this->attemptInterval, fn () => $this->runInternal());
                }
            );
        } catch (Throwable $exception) {
            // todo: enhance the context
            $this->logger->critical('Unable to schedule a background task: price retrieving.');
        }
    }
}
