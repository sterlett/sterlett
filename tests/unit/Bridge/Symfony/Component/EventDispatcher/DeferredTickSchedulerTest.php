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

namespace Sterlett\Tests\Bridge\Symfony\Component\EventDispatcher;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use React\EventLoop\StreamSelectLoop;
use stdClass;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredTickScheduler;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\TickCallbackBuilder;
use Symfony\Contracts\EventDispatcher\Event;

final class DeferredTickSchedulerTest extends TestCase
{
    /**
     * Stream select event loop
     *
     * @var StreamSelectLoop
     */
    private StreamSelectLoop $loop;

    /**
     * Performs event subscriber callbacks registration in the ReactPHP environment, deferred approach
     *
     * @var DeferredTickScheduler
     */
    private DeferredTickScheduler $deferredTickScheduler;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->loop = new StreamSelectLoop();

        $loggerStub      = $this->createStub(LoggerInterface::class);
        $callbackBuilder = new TickCallbackBuilder($loggerStub);

        $this->deferredTickScheduler = new DeferredTickScheduler($this->loop, $callbackBuilder);
    }

    /**
     * Tests listener calls execution and checks that event contains expected result data
     *
     * Side checks:
     * - Callback execution order
     * - Exception will not stop the entire event loop
     *
     * @return void
     */
    public function testEventListenerCallsExecutedAsLoopTick(): void
    {
        $event         = new stdClass();
        $event->detail = [];

        $listeners = function () {
            yield fn ($event, $eventName, $eventDispatcher) => $event->detail[] = 'callbackOneResult';
            yield function ($event, $eventName, $eventDispatcher) {
                // this exception should not stop the entire event loop.
                throw new Exception();
            };
            yield fn ($event, $eventName, $eventDispatcher) => $event->detail[] = 'callbackTwoResult';
            yield fn ($event, $eventName, $eventDispatcher) => $event->detail[] = 'callbackThreeResult';
        };

        $this->deferredTickScheduler->scheduleListenerCalls($listeners(), 'eventName', $event);

        $this->assertEmpty($event->detail, "Event shouldn't contain any result data before loop run.");

        $this->loop->run();

        $this->assertEquals(
            [
                'callbackOneResult',
                'callbackTwoResult',
                'callbackThreeResult',
            ],
            $event->detail,
            "Event doesn't contain expected result data or order isn't preserved."
        );
    }

    /**
     * Tests listener calls execution and checks that event propagation can be stopped
     *
     * @return void
     */
    public function testResolvedEventWillNotPropagateInLoopTick(): void
    {
        $event         = new Event();
        $event->detail = [];

        $listeners = function () {
            yield fn ($event, $eventName, $eventDispatcher) => $event->detail[] = 'callbackOneResult';
            yield function ($event, $eventName, $eventDispatcher) {
                $event->detail[] = 'callbackTwoResult';

                /** @var Event $event */
                $event->stopPropagation();
            };
            yield fn ($event, $eventName, $eventDispatcher) => $event->detail[] = 'callbackThreeResult';
        };

        $this->deferredTickScheduler->scheduleListenerCalls($listeners(), 'eventName', $event);

        $this->assertEmpty($event->detail, "Event shouldn't contain any result data before loop run.");

        $this->loop->run();

        $this->assertTrue(
            $event->isPropagationStopped(),
            'Event should be marked as resolved according to the StoppableEventInterface contract.'
        );

        $this->assertEquals(
            [
                'callbackOneResult',
                'callbackTwoResult',
            ],
            $event->detail,
            "Event should contain result in state, defined before it has resolved."
        );
    }
}
