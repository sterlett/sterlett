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

use Psr\Container\ContainerInterface;
use React\EventLoop\LoopInterface;

/**
 * Provides the opportunity to run an asynchronous web server with some background tasks in PHP environment (concurrent
 * approach is based on event loop and non-blocking I/O). Uses an implementation of PSR-11 container to manage
 * dependencies.
 *
 * @see https://www.php-fig.org/psr/psr-11
 */
final class Application
{
    /**
     * Gives access to object instances (services)
     *
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * Application constructor.
     *
     * @param ContainerInterface $container Gives access to object instances (services)
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Starts ReactPHP event loop and socket server
     *
     * @return void
     */
    public function run(): void
    {
        /** @var ServerInterface $server */
        $server = $this->container->get('app.server');
        $server->up();

        /** @var LoopInterface $loop */
        $loop = $this->container->get('app.event_loop');
        $this->setShutdownConditions($loop);

        $this->container->get('service_warmer');

        // starting routines / background tasks.
        $priceRetrievingRoutine = $this->container->get('app.routine.price_retrieving');
        $priceRetrievingRoutine->run();

        $ratioFeedRoutine = $this->container->get('app.routine.vbratio_feed');
        $ratioFeedRoutine->run();

        $loop->run();
    }

    /**
     * Binds signals for loop's termination
     *
     * @param LoopInterface $loop Event loop
     *
     * @return void
     */
    private function setShutdownConditions(LoopInterface $loop): void
    {
        $shutdown = $this->container->get('app.shutdown');

        $loop->addSignal(SIGINT, $shutdown);
        $loop->addSignal(SIGTERM, $shutdown);
    }
}
