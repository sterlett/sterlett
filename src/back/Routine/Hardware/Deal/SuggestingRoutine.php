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

namespace Sterlett\Routine\Hardware\Deal;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use RuntimeException;
use Sterlett\Hardware\Deal\Observer as DealObserver;
use Sterlett\RoutineInterface;
use Throwable;

/**
 * Runs a background task, which suggests best deals for available hardware (based on the Price/Performance analysis)
 */
class SuggestingRoutine implements RoutineInterface
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
     * Monitors hardware prices from the different sellers and suggests the best deals, in terms of Price/Performance
     *
     * @var DealObserver
     */
    private DealObserver $dealObserver;

    /**
     * The interval between deal suggestion attempts, in seconds (8h by default)
     *
     * @var float
     */
    private float $attemptInterval;

    /**
     * SuggestingRoutine constructor.
     *
     * @param LoggerInterface $logger          Logs routine activity
     * @param LoopInterface   $loop            Event loop reference
     * @param DealObserver    $dealObserver    Monitors hardware prices from the different sellers and suggests deals
     * @param float           $attemptInterval The interval between price retrieving attempts, in seconds
     */
    public function __construct(
        LoggerInterface $logger,
        LoopInterface $loop,
        DealObserver $dealObserver,
        float $attemptInterval = 28800.0
    ) {
        $this->logger          = $logger;
        $this->loop            = $loop;
        $this->dealObserver    = $dealObserver;
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
     * Runs deal suggesting logic in the background task (as a timer)
     *
     * @return void
     */
    private function runInternal(): void
    {
        $this->logger->info('Executing a background task: deal suggestion.');

        try {
            $completionPromise = $this->dealObserver->suggestDeals();

            $completionPromise->then(
                function () {
                    $this->logger->info('Deal suggestion task is complete.');

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

                    $this->logger->critical('Deal suggestion task has failed.');

                    $this->loop->addTimer($this->attemptInterval, fn () => $this->runInternal());
                }
            );
        } catch (Throwable $exception) {
            // todo: enhance the context

            $this->logger->critical('Unable to schedule a background task: deal suggestion.');
        }
    }
}
