<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Bridge\Symfony\Component\EventDispatcher;

use Psr\EventDispatcher\StoppableEventInterface;
use React\EventLoop\LoopInterface;

/**
 * Provides a simple and straightforward way to make services observe application-level events in the ReactPHP
 * environment. Each listener's callback will be scheduled as the "work to be completed in the future" within event
 * loop that guarantees natural execution according to the original loop implementation.
 *
 * Event propagation is still managed using the StoppableEventInterface. Callback order is preserved (see future tick
 * contract).
 *
 * Exception, raised by a single listener, doesn't stop the event loop and any part of callback planning logic.
 *
 * @see LoopInterface::futureTick
 */
class TickScheduler implements TickSchedulerInterface
{
    /**
     * Event loop
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * Builds callbacks with listener calls for React's future ticks queue
     *
     * @var TickCallbackBuilder
     */
    private TickCallbackBuilder $callbackBuilder;

    /**
     * TickScheduler constructor.
     *
     * @param LoopInterface       $loop            Event loop
     * @param TickCallbackBuilder $callbackBuilder Builds callbacks with listener calls for React's future ticks queue
     */
    public function __construct(LoopInterface $loop, TickCallbackBuilder $callbackBuilder)
    {
        $this->loop            = $loop;
        $this->callbackBuilder = $callbackBuilder;
    }

    /**
     * {@inheritDoc}
     */
    public function scheduleListenerCalls(iterable $listeners, string $eventName, object $event): void
    {
        foreach ($listeners as $listener) {
            $tickCallback = $this->callbackBuilder->makeTickCallback($listener, $eventName, $event);

            $propagationAwareTickCallback = $this->makePropagationAwareTickCallback($tickCallback, $event);

            $this->loop->futureTick($propagationAwareTickCallback);
        }
    }

    /**
     * Returns a callback that prevents listener call if event is marked as resolved
     *
     * @param callable $tickCallback Calls listener and passes an event to it
     * @param object   $event        The event object provided for the event listener
     *
     * @return callable
     */
    private function makePropagationAwareTickCallback(callable $tickCallback, object $event): callable
    {
        return function () use ($tickCallback, $event) {
            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                return;
            }

            $tickCallback();
        };
    }
}
