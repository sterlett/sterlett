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

use ArrayIterator;
use Iterator;
use IteratorIterator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Traversable;

/**
 * Provides a way to make services observe application-level events without taking too much continuous time from the
 * shared execution flow. The implementation is similar to the TickScheduler except new callbacks for the future tick
 * queue will not be added to the event loop if propagation stops early.
 */
class DeferredTickScheduler implements TickSchedulerInterface
{
    /**
     * Event loop
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * Builds callbacks with listener calls for React's future tick queue
     *
     * @var TickCallbackBuilder
     */
    private TickCallbackBuilder $callbackBuilder;

    /**
     * Forwards a result value of the event dispatch promise, if there are no listeners to do it explicitly
     *
     * @var DispatchPromiseResolver
     */
    private DispatchPromiseResolver $dispatchPromiseResolver;

    /**
     * DeferredTickScheduler constructor.
     *
     * @param LoopInterface           $loop                    Event loop
     * @param TickCallbackBuilder     $callbackBuilder         Builds callbacks with listener calls for the tick queue
     * @param DispatchPromiseResolver $dispatchPromiseResolver Forwards a result value of the event dispatch promise
     */
    public function __construct(
        LoopInterface $loop,
        TickCallbackBuilder $callbackBuilder,
        DispatchPromiseResolver $dispatchPromiseResolver
    ) {
        $this->loop                    = $loop;
        $this->callbackBuilder         = $callbackBuilder;
        $this->dispatchPromiseResolver = $dispatchPromiseResolver;
    }

    /**
     * {@inheritDoc}
     */
    public function scheduleListenerCalls(
        EventDispatcherInterface $eventDispatcher,
        iterable $listeners,
        string $eventName,
        object $event
    ): void {
        if ($listeners instanceof Traversable) {
            $listenerIterator = new IteratorIterator($listeners);
        } else {
            $listenerIterator = new ArrayIterator($listeners);
        }

        $listenerIterator->rewind();

        $this->scheduleListenerCallsRecursive($eventDispatcher, $listenerIterator, [], $eventName, $event);
    }

    /**
     * Adds a new listener call from the event dispatching chain to the loop's tick queue using iterator
     *
     * @param EventDispatcherInterface $eventDispatcher  Dispatcher that triggered the event
     * @param Iterator                 $listenerIterator Provides access to the chain of listeners
     * @param array                    $listenerPromises For async listeners, to properly resolve dispatch promise
     * @param string                   $eventName        Name of the event to dispatch
     * @param object                   $event            The event object for the event listener
     *
     * @return void
     */
    private function scheduleListenerCallsRecursive(
        EventDispatcherInterface $eventDispatcher,
        Iterator $listenerIterator,
        array $listenerPromises,
        string $eventName,
        object $event
    ): void {
        if (!$listenerIterator->valid()) {
            if ($event instanceof DeferredEventInterface) {
                // resolving a dispatch promise.
                $this->dispatchPromiseResolver->resolveDispatchPromise($event, $listenerPromises);
            }

            return;
        }

        $listener = $listenerIterator->current();

        $tickCallback = $this->callbackBuilder->makeTickCallback($eventDispatcher, $listener, $eventName, $event);

        // todo: extract a dto for the tick context that will hold all data
        $this->loop->futureTick(
            function () use (
                $tickCallback,
                $eventDispatcher,
                $listenerIterator,
                $listenerPromises,
                $eventName,
                $event
            ) {
                // callback for the current tick queue flush.
                $promiseOrNull = $tickCallback();

                if ($promiseOrNull instanceof PromiseInterface) {
                    $listenerPromises[] = $promiseOrNull;
                }

                if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                    return;
                }

                $listenerIterator->next();

                // next callback doesn't participate in the current tick queue flush, so the whole event dispatching
                // routine doesn't slow down other activities, e.g. HTTP requests handling and periodic timers.
                $this->scheduleListenerCallsRecursive(
                    $eventDispatcher,
                    $listenerIterator,
                    $listenerPromises,
                    $eventName,
                    $event
                );
            }
        );
    }
}
