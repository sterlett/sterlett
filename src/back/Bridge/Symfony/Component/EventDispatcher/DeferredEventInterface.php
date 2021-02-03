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

namespace Sterlett\Bridge\Symfony\Component\EventDispatcher;

use React\Promise\Deferred;

/**
 * Marks the event as a deferred one, i.e. will be processed by the future tick queue
 */
interface DeferredEventInterface
{
    /**
     * Returns a deferred object, associated with this event instance
     *
     * @return Deferred
     */
    public function getDeferred(): Deferred;
}
