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

namespace Sterlett\Event;

use React\Promise\Deferred;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventInterface;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Describes an event, when the application is going to be stopped
 */
class ShutdownStartedEvent extends Event implements DeferredEventInterface
{
    use DeferredEventTrait;

    /**
     * Event name
     *
     * @var string
     */
    public const NAME = 'app.event.shutdown_started';

    /**
     * ShutdownStartedEvent constructor.
     */
    public function __construct()
    {
        $this->_deferred = new Deferred();
    }
}
