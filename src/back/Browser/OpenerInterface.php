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

namespace Sterlett\Browser;

use React\Promise\PromiseInterface;

/**
 * Opens a remote browser and acquires a browsing session
 */
interface OpenerInterface
{
    /**
     * Returns a promise that resolves to a browsing context instance, filled with the session-specific information to
     * perform actions in the remote browser
     *
     * @return PromiseInterface<Context>
     */
    public function openBrowser(): PromiseInterface;
}
