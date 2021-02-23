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
use React\Promise\PromiseInterface;

/**
 * Marks the event as a deferred one, i.e. will be processed by the future tick queue
 */
interface DeferredEventInterface
{
    /**
     * Returns a promise, which being resolved, will forward a value from the responsible listener or event
     * dispatcher. NULL will be returned in case, when the event is already dispatched.
     *
     * @return PromiseInterface<mixed>|null
     */
    public function getPromise(): ?PromiseInterface;

    /**
     * Returns a deferred object, associated with this event instance, or a NULL if it is already resolved/rejected
     * (this behavior part is similar to the "propagation stopped" flag).
     *
     * The caller MUST explicitly resolve or reject a promise using this interface. By calling this method it takes
     * full responsibility for the further result/error forwarding. if no such responsible listeners are present, the
     * event dispatcher itself will take care of it and will forward a NULL value to the onFulfilled callback.
     *
     * This method MUST return a NULL value, if a deferred object is "taken" at least once.
     *
     * @return Deferred|null
     *
     * @see DeferredEventTrait
     */
    public function takeDeferred(): ?Deferred;
}
