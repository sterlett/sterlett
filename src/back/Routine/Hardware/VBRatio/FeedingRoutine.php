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

namespace Sterlett\Routine\Hardware\VBRatio;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use RuntimeException;
use Sterlett\Hardware\VBRatio\Feeder as VBRatioFeeder;
use Sterlett\RoutineInterface;
use Throwable;

/**
 * Runs a background task, which updates V/B ratio list for the HTTP handler
 */
class FeedingRoutine implements RoutineInterface
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
     * Emits a list with V/B ranks for the available hardware items
     *
     * @var VBRatioFeeder
     */
    private VBRatioFeeder $ratioFeeder;

    /**
     * The interval between feed updates, in seconds (default: 4h)
     *
     * @var float
     */
    private float $attemptInterval;

    /**
     * FeedingRoutine constructor.
     *
     * @param LoggerInterface $logger          Logs routine activity
     * @param LoopInterface   $loop            Event loop reference
     * @param VBRatioFeeder   $ratioFeeder     Emits a list with V/B ranks for the available hardware items
     * @param float           $attemptInterval The interval between feed updates, in seconds (default: 4h)
     */
    public function __construct(
        LoggerInterface $logger,
        LoopInterface $loop,
        VBRatioFeeder $ratioFeeder,
        float $attemptInterval = 14400.0
    ) {
        $this->logger          = $logger;
        $this->loop            = $loop;
        $this->ratioFeeder     = $ratioFeeder;
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
     * Runs V/B ratio list feed logic in the background task (as a timer)
     *
     * @return void
     */
    private function runInternal(): void
    {
        $this->logger->info('Executing a background task: V/B ratio feed.');

        try {
            $completionPromise = $this->ratioFeeder->emitRatios();

            $completionPromise->then(
                function () {
                    $this->logger->info('V/B ratio feed task is complete.');

                    // rescheduling, to perform a next attempt.
                    $this->loop->addTimer($this->attemptInterval, fn () => $this->runInternal());
                },
                function (Throwable $rejectionReason) {
                    $reasonNext = new RuntimeException('', 0, $rejectionReason);

                    while ($reasonNext = $reasonNext->getPrevious()) {
                        $exceptionMessage = $reasonNext->getMessage();

                        $this->logger->error($exceptionMessage);
                    }

                    $this->logger->critical('V/B ratio feed task has failed.', ['reason' => $rejectionReason]);

                    $this->loop->addTimer($this->attemptInterval, fn () => $this->runInternal());
                }
            );
        } catch (Throwable $exception) {
            // todo: enhance the context

            $this->logger->critical(
                'Unable to schedule a background task: V/B ratio feed.',
                [
                    'exception' => $exception,
                ]
            );
        }
    }
}
