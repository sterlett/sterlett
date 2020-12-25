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

namespace Sterlett\Bridge\React\EventLoop;

use React\Promise\PromiseInterface;

/**
 * Allocates execution time in the centralized event loop for specific code, which should be executed with delay / or
 * at custom period of time in the future (i.e. encapsulates timers management for such logic)
 */
interface TimeIssuerInterface
{
    /**
     * Returns a promise that resolves when the "time issuer" is ready to execute user-side callback.
     *
     * The value of the resolved promise will be a reference to the time issuer instance itself. When the job is done,
     * user-side MUST call {@link release()} to decrease the internal concurrency counter and do other cleanup routines
     * (if any).
     *
     * @return PromiseInterface<TimeIssuerInterface>
     */
    public function getTime(): PromiseInterface;

    /**
     * Should be called on the user-side, when the execution time frame, which has been allocated, is freed
     *
     * @return void
     */
    public function release(): void;
}
