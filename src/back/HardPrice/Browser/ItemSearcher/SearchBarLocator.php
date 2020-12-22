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

namespace Sterlett\HardPrice\Browser\ItemSearcher;

use React\Promise\PromiseInterface;
use Sterlett\Browser\Context as BrowserContext;

/**
 * Finds an element on the page, which is suited for item search
 */
class SearchBarLocator
{
    /**
     * Returns a promise that resolves to a string, representing an internal WebDriver handle of the search input
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<string>
     */
    public function locateSearchBar(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $elementIdentifierPromise = $webDriver->getElementIdentifier(
            $sessionIdentifier,
            '//form[@name="search"]//input[@type="text"]'
        );

        return $elementIdentifierPromise;
    }
}
