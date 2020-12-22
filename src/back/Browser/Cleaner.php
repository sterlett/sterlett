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

namespace Sterlett\Browser;

use React\Promise\PromiseInterface;

/**
 * Stops a remote browser session and cleans all related resources
 */
class Cleaner
{
    /**
     * Returns a promise that will be resolved when a remote WebDriver service stops browsing session and cleans
     * related data
     *
     * @param Context $context Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    public function cleanBrowser(Context $context): PromiseInterface
    {
        $webDriver         = $context->getWebDriver();
        $sessionIdentifier = $context->getHubSession();

        $quitPromise = $webDriver->removeSession($sessionIdentifier);

        return $quitPromise;
    }
}
