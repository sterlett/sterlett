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
 * Resets state of the page from the currently active browser tab using a given context
 */
class Refresher
{
    /**
     * Sends URI open command to the Grid server and returns a promise that will be resolved when the contents from the
     * active tab become refreshed
     *
     * @param Context $context Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    public function refreshTab(Context $context): PromiseInterface
    {
        $webDriver         = $context->getWebDriver();
        $sessionIdentifier = $context->getHubSession();

        $uriReopenPromise = $webDriver
            // reading current URI to perform a reset operation.
            ->getCurrentUri($sessionIdentifier)
            // executing a reset.
            ->then(fn (string $uriCurrent) => $webDriver->openUri($sessionIdentifier, $uriCurrent))
        ;

        return $uriReopenPromise;
    }
}
