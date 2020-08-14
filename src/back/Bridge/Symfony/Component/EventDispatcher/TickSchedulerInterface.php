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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Performs event subscriber callbacks registration in the ReactPHP environment for asynchronous execution using
 * future ticks concept
 */
interface TickSchedulerInterface
{
    /**
     * Adds callbacks from the listeners to the ReactPHP environment for asynchronous execution
     *
     * @param EventDispatcherInterface $eventDispatcher Dispatcher that triggered the event
     * @param callable[]               $listeners       The event listeners for asynchronous execution
     * @param string                   $eventName       The name of the event to dispatch
     * @param object                   $event           The event object to pass to the event listener
     *
     * @return void
     *
     * @see EventDispatcher::callListeners
     */
    public function scheduleListenerCalls(
        EventDispatcherInterface $eventDispatcher,
        iterable $listeners,
        string $eventName,
        object $event
    ): void;
}
