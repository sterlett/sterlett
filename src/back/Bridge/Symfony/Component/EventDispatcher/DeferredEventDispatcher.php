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

namespace Sterlett\Bridge\Symfony\Component\EventDispatcher;

use InvalidArgumentException;
use React\Promise\PromiseInterface;
use Symfony\Component\EventDispatcher\EventDispatcher as BaseEventDispatcher;

/**
 * Dispatches application-level events using future tick queue of the ReactPHP event loop.
 *
 * Uses a dedicated service to schedule listener callbacks as loop's ticks. There are a few implementations of tick
 * scheduler allowing to achieve this goal, one simply registers all listener calls as one-off set of callbacks for the
 * future tick, one by one in the order they are subscribed to the event (straightforward way; event propagation
 * behavior is handled within each callback separately). The other one implementation registers each listener call as
 * part of the next loop tick "by demand", using built-in iterators, so no extra callbacks will be created for the tick
 * queue if event becomes "resolved"; this approach will be useful in case when there are many concurrent event
 * listeners and context switching must be planned more carefully to maintain true async execution flow.
 *
 * Any payload attached to the event shouldn't be taken immediately, right after dispatch() is triggered.
 *
 * @see TickScheduler
 * @see DeferredTickScheduler
 */
class DeferredEventDispatcher extends BaseEventDispatcher
{
    /**
     * Registers listener callbacks in the ReactPHP environment for asynchronous execution
     *
     * @var TickSchedulerInterface
     */
    private TickSchedulerInterface $tickScheduler;

    /**
     * DeferredEventDispatcher constructor.
     *
     * @param TickSchedulerInterface $tickScheduler Registers listener callbacks in the ReactPHP environment
     */
    public function __construct(TickSchedulerInterface $tickScheduler)
    {
        parent::__construct();

        $this->tickScheduler = $tickScheduler;
    }

    /**
     * {@inheritDoc}
     *
     * @return PromiseInterface<DeferredEventInterface>
     */
    public function dispatch(object $event, string $eventName = null): PromiseInterface
    {
        if (!$event instanceof DeferredEventInterface) {
            throw new InvalidArgumentException(
                'Event should implement the DeferredEventInterface to be handled by the deferred event dispatcher.'
            );
        }

        /** @var DeferredEventInterface $event */
        $event = parent::dispatch($event, $eventName);

        $dispatchingDeferred       = $event->getDeferred();
        $propagationStoppedPromise = $dispatchingDeferred->promise();

        return $propagationStoppedPromise;
    }

    /**
     * {@inheritDoc}
     */
    protected function callListeners(iterable $listeners, string $eventName, object $event)
    {
        $this->tickScheduler->scheduleListenerCalls($this, $listeners, $eventName, $event);
    }
}
