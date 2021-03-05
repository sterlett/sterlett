<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020-2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Bridge\React\EventLoop\TimeIssuer;

use Ds\Queue;
use Ds\Stack;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Throwable;

/**
 * Delays all callbacks to maintain the configured APS (actions per second) count for the user-side service context.
 *
 * User-side callbacks will be stored in instances of the Deferred class (as resolvers) and will be called as the
 * timer fires.
 */
class SequentialTimeIssuer implements TimeIssuerInterface
{
    /**
     * Event loop that is used to manage timers
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * Options for the time issuer
     *
     * @var array
     */
    private array $options;

    /**
     * Internal queue/stack with delayed actions (callbacks)
     *
     * @var Queue|Stack
     */
    private $_callbacksPending;

    /**
     * An internal counter, that is used to maintain configured throughput
     *
     * @var int
     */
    private int $_concurrencyCounter;

    /**
     * SequentialTimeIssuer constructor.
     *
     * @param LoopInterface $loop    Event loop that is used to manage timers
     * @param array         $options Options for the time issuer
     */
    public function __construct(LoopInterface $loop, array $options)
    {
        $this->loop = $loop;

        $optionsResolver = new OptionsResolver();

        $optionsResolver
            ->define('actions_per_second')
            ->info('The limit for all actions (callbacks), per second')
            ->allowedTypes('int', 'float')
            ->default(1.0)
            ->normalize(
                function (Options $options, $actionsPerSecond) {
                    $actionsPerSecondNormalized = (float) max(0.1, $actionsPerSecond);

                    return $actionsPerSecondNormalized;
                }
            )
        ;

        $optionsResolver
            ->define('concurrent_actions')
            ->info('Max count of pending actions (callbacks) at the same time')
            ->allowedTypes('int')
            ->default(1)
            ->normalize(
                function (Options $options, int $concurrentActions) {
                    $concurrentActionsNormalized = max(1, $concurrentActions);

                    return $concurrentActionsNormalized;
                }
            )
        ;

        $optionsResolver
            ->define('actions_delay_min')
            ->info('Minimum delay for each subsequent action (callback), in seconds')
            ->allowedTypes('float')
            ->default(0.001)
            ->normalize(
                function (Options $options, float $actionsDelayMin) {
                    $actionsDelayMinNormalized = max(0.001, $actionsDelayMin);

                    return $actionsDelayMinNormalized;
                }
            )
        ;

        $optionsResolver
            ->define('actions_delay_max')
            ->info('Maximum delay for each subsequent action, in seconds')
            ->allowedTypes('float')
            ->default(0.001)
            ->normalize(
                function (Options $options, float $actionsDelayMax) {
                    $actionsDelayMaxNormalized = max(0.001, $actionsDelayMax);

                    return $actionsDelayMaxNormalized;
                }
            )
        ;

        $optionsResolver
            ->define('is_stack')
            ->info('Determines order for pending actions, FIFO/LIFO (default queue, FIFO)')
            ->allowedTypes('bool')
            ->default(false)
        ;

        $this->options = $optionsResolver->resolve($options);

        $this->_callbacksPending   = $this->options['is_stack'] ? new Stack() : new Queue();
        $this->_concurrencyCounter = 0;

        $this->registerTimer();
    }

    /**
     * {@inheritDoc}
     */
    public function getTime(): PromiseInterface
    {
        $timeDeferred = new Deferred();

        $this->_callbacksPending->push($timeDeferred);

        $timePromise = $timeDeferred->promise();

        return $timePromise;
    }

    /**
     * {@inheritDoc}
     */
    public function release(): void
    {
        --$this->_concurrencyCounter;
    }

    /**
     * Adds a timer for the event loop that calls user-side callbacks one by one, maintaining configured throughput
     *
     * @return void
     */
    private function registerTimer(): void
    {
        $intervalInSeconds = $this->calculateInterval();

        // todo: should probably use resolve() from promise-timer instead
        $this->loop->addPeriodicTimer(
            $intervalInSeconds,
            function (TimerInterface $timerItself) {
                // registering a timer with new interval.
                $this->loop->cancelTimer($timerItself);
                $this->registerTimer();

                if ($this->_callbacksPending->isEmpty()) {
                    return;
                }

                if ($this->_concurrencyCounter >= $this->options['concurrent_actions']) {
                    return;
                }

                /** @var Deferred $timeDeferred */
                $timeDeferred = $this->_callbacksPending->pop();

                try {
                    ++$this->_concurrencyCounter;

                    $timeDeferred->resolve($this);
                } catch (Throwable $exception) {
                    // todo: error log record.
                    // 'Unable to execute a delayed callback.'
                }
            }
        );
    }

    /**
     * Returns a firing interval for the timer, based on the configured APS and a random delay (optional)
     *
     * @return float
     */
    private function calculateInterval(): float
    {
        // calculated interval for user-side code calls (based on the given APS count).
        $intervalCalculated = 1.0 / $this->options['actions_per_second'];

        // applying random delay.
        $delayMin = $this->options['actions_delay_min'];
        $delayMax = $this->options['actions_delay_max'];

        $delayRandom        = $delayMin + ($delayMax - $delayMin) * (mt_rand() / mt_getrandmax());
        $intervalCalculated = $intervalCalculated + $delayRandom;

        $intervalNormalized = round($intervalCalculated, 3);

        return $intervalNormalized;
    }
}
