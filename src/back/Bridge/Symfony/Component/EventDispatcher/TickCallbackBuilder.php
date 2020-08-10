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

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Builds callbacks with listener calls for React's future tick queue.
 *
 * Encapsulates all requirements from the LoopInterface to ensure user-defined callbacks will be safely executed in the
 * shared event loop.
 */
class TickCallbackBuilder
{
    /**
     * Logs exceptions, suppressed by the future tick callbacks according to the LoopInterface requirements
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * TickCallbackBuilder constructor.
     *
     * @param LoggerInterface $logger Logs exceptions, suppressed by the future tick callbacks
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns a callback for the future tick with listener call logic
     *
     * @param callable $listener  The event listener
     * @param string   $eventName The name of the event to dispatch
     * @param object   $event     The event object to pass to the event listener
     *
     * @return callable
     */
    public function makeTickCallback(callable $listener, string $eventName, object $event): callable
    {
        return function () use ($listener, $eventName, $event) {
            try {
                $listener($event, $eventName, $this);
            } catch (Exception $exception) {
                $exceptionCode    = $exception->getCode();
                $exceptionMessage = $exception->getMessage();

                $this->logger->error(
                    'An error has been occurred during future tick callback execution. '
                    . '({exceptionCode}){exceptionMessage}',
                    [
                        'exceptionCode'    => $exceptionCode,
                        'exceptionMessage' => $exceptionMessage,
                    ]
                );
            }
        };
    }
}
