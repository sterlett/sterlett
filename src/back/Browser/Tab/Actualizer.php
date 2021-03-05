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

namespace Sterlett\Browser\Tab;

use React\Promise\PromiseInterface;
use Sterlett\Browser\Context as BrowserContext;

/**
 * Updates a list with available browser tabs (window handles) for the browser context
 */
class Actualizer
{
    /**
     * Updates the context to include a new set of browser tab identifiers (handles) from the WebDriver
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    public function actualizeTabs(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $tabListUpdatePromise = $webDriver
            // acquiring available browser tabs.
            ->getTabIdentifiers($sessionIdentifier)
            // setting up new tab identifiers for the context.
            ->then(fn (array $tabIdentifiers) => $browserContext->setTabIdentifiers($tabIdentifiers))
        ;

        return $tabListUpdatePromise;
    }
}
