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
use function React\Promise\all;

/**
 * Forwards a result value of the event dispatch promise, if there are no listeners to do it explicitly
 * (a default fallback)
 */
class DispatchPromiseResolver
{
    /**
     * Resolves the event dispatch promise. It is considered as "done" if all listener promises (if any) are
     * successfully resolved/rejected. Ignores any branching, just ensures all async operations are complete before
     * marking a dispatch promise as "fulfilled".
     *
     * @param DeferredEventInterface $event            An event that has been successfully dispatched
     * @param PromiseInterface[]     $listenerPromises A set of promises from the async listeners
     *
     * @return void
     */
    public function resolveDispatchPromise(DeferredEventInterface $event, array $listenerPromises): void
    {
        $dispatchingDeferred = $event->takeDeferred();

        // already resolved (or rejected) by the responsible event listener.
        if (!$dispatchingDeferred instanceof Deferred) {
            return;
        }

        // waiting for the event loop and/or other async services, to properly handle all listener promises, no matter
        // will they become truly resolved or rejected; otherwise we may encounter some border cases, for example,
        // when the application stops and there are some pending promises, which will remain unresolved; resolve/reject
        // logic MUST be applied on the listener's side.
        all($listenerPromises)->then(
            fn () => $dispatchingDeferred->resolve(null),
            fn () => $dispatchingDeferred->resolve(null)
        );
    }
}
