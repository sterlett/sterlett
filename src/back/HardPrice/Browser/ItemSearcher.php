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

namespace Sterlett\HardPrice\Browser;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Dto\Hardware\Item;
use Sterlett\HardPrice\Browser\ItemSearcher\SearchBarLocator;
use Throwable;

/**
 * Performs actions in the remove browser to find a page with hardware item information
 */
class ItemSearcher
{
    /**
     * Finds an element on the page, which is suited for item search
     *
     * @var SearchBarLocator
     */
    private SearchBarLocator $searchBarLocator;

    /**
     * ItemSearcher constructor.
     *
     * @param SearchBarLocator $searchBarLocator Finds an element on the page, which is suited for item search
     */
    public function __construct(SearchBarLocator $searchBarLocator)
    {
        $this->searchBarLocator = $searchBarLocator;
    }

    /**
     * Returns a promise that will be resolved when the item page becomes open (and fully loaded)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<null>
     */
    public function searchItem(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        $searchReadyPromise = $this->searchBarLocator
            // resolving search bar element on the page / navigating to the search page.
            ->locateSearchBar($browserContext)
            // focusing an input to send a search query.
            ->then(fn (string $elementIdentifier) => $this->focusSearchBar($browserContext, $elementIdentifier))
        ;

        $searchQueryPromise = $searchReadyPromise
            // sending a search query.
            ->then(fn () => $this->typeSearchQuery($browserContext, $item))
            // waiting for the results.
            ->then(fn () => $this->waitSearchResults($browserContext, $item))
        ;

        $pageAccessPromise = $searchQueryPromise
            ->then(fn () => $this->openItemPage($browserContext, $item))
            ->then(
                null,
                function (Throwable $rejectionReason) {
                    throw new RuntimeException('Unable to find (and open) an item page.', 0, $rejectionReason);
                }
            )
        ;

        return $pageAccessPromise;
    }

    /**
     * Returns a promise that will be resolved when the search input becomes active
     *
     * @param BrowserContext $browserContext    Holds browser state and a driver reference to perform actions
     * @param string         $elementIdentifier An internal WebDriver handle for the search input
     *
     * @return PromiseInterface<null>
     */
    private function focusSearchBar(BrowserContext $browserContext, string $elementIdentifier): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        // todo
    }

    /**
     * Returns a promise that will be resolved when the remove WebDriver service confirms a text type operation for the
     * search input
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<null>
     */
    private function typeSearchQuery(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        // todo
    }

    /**
     * Returns a promise that will be resolved when the remote browser completes search results rendering
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<null>
     */
    private function waitSearchResults(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        // todo
    }

    /**
     * Returns a promise that will be resolved when an item page, from the search results, becomes open
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<null>
     */
    private function openItemPage(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        // todo
    }
}
