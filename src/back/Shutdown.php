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

namespace Sterlett;

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventDispatcher;
use Sterlett\Event\ShutdownStartedEvent;

/**
 * Encapsulates graceful shutdown logic; use this service whenever you want to terminate application at some custom
 * execution point.
 */
class Shutdown
{
    /**
     * Performs logging for cleanup procedures
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Provides hooks on domain-specific lifecycles
     *
     * @var DeferredEventDispatcher
     */
    private DeferredEventDispatcher $eventDispatcher;

    /**
     * Event loop
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * Shutdown constructor.
     *
     * @param LoggerInterface         $logger          Performs logging for cleanup procedures
     * @param DeferredEventDispatcher $eventDispatcher Provides hooks on domain-specific lifecycles
     * @param LoopInterface           $loop            Event loop
     */
    public function __construct(LoggerInterface $logger, DeferredEventDispatcher $eventDispatcher, LoopInterface $loop)
    {
        $this->logger          = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->loop            = $loop;
    }

    /**
     * Stops the event loop and performs any other cleanup routines
     *
     * @return void
     */
    public function execute(): void
    {
        $event = new ShutdownStartedEvent();

        $shutdownReadyPromise = $event->getPromise();
        $shutdownReadyPromise->then(fn () => $this->doShutdown(), fn () => $this->doShutdown());

        $this->eventDispatcher->dispatch($event, ShutdownStartedEvent::NAME);
    }

    /**
     * Registers a shutdown tick that will stop the event loop
     *
     * @return void
     */
    private function doShutdown(): void
    {
        $this->logger->info('Stopping event loop...');

        $this->loop->addTimer(0.5, fn () => $this->loop->stop());
    }

    /**
     * Performs proxy pass to the shutdown execution logic
     *
     * @return void
     */
    public function __invoke()
    {
        $this->execute();
    }
}
