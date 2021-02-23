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
 * Forwards a result value of the event dispatch promise, if there are no listeners to do it explicitly
 * (a default fallback)
 */
class DispatchPromiseResolver
{
    /**
     * Resolves the event dispatch promise
     *
     * @param DeferredEventInterface $event An event that has been successfully dispatched
     *
     * @return void
     */
    public function resolveDispatchPromise(DeferredEventInterface $event): void
    {
        $dispatchingDeferred = $event->takeDeferred();

        // already resolved (or rejected) by the responsible event listener.
        if (!$dispatchingDeferred instanceof Deferred) {
            return;
        }

        $dispatchingDeferred->resolve(null);
    }
}
