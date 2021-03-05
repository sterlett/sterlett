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

namespace Sterlett\HardPrice\Browser;

use React\Promise\PromiseInterface;
use Sterlett\Browser\Context as BrowserContext;

/**
 * Will perform actions to open a website in the remote browser
 */
interface NavigatorInterface
{
    /**
     * Returns a promise that will be resolved when the website becomes open in the remote browser
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    public function navigate(BrowserContext $browserContext): PromiseInterface;
}
