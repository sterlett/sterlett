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

use React\Promise\Deferred;
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
     * Timeout for waitUntil condition checks, which ensures all page data is loaded (default: 30.0)
     *
     * @var float
     */
    private float $ajaxTimeout;

    /**
     * ItemSearcher constructor.
     *
     * @param SearchBarLocator $searchBarLocator Finds an element on the page, which is suited for item search
     * @param float            $ajaxTimeout      Timeout for waitUntil condition checks
     */
    public function __construct(SearchBarLocator $searchBarLocator, float $ajaxTimeout = 30.0)
    {
        $this->searchBarLocator = $searchBarLocator;
        $this->ajaxTimeout      = max(0.1, $ajaxTimeout);
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
        $searchQueryPromise = $this
            ->prepareSearchBar($browserContext)
            // sending a search query.
            ->then(
                function (array $searchBarIdentifier) use ($browserContext, $item) {
                    return $this->doSearchQuery($browserContext, $searchBarIdentifier, $item);
                }
            )
        ;

        $linkIdentifierPromise = $searchQueryPromise
            // waiting for the results.
            // todo: extract results analyser
            ->then(fn () => $this->waitSearchResults($browserContext, $item))
            // applying a divergence delay.
            ->then(
                function (array $linkIdentifier) use ($browserContext) {
                    $webDriver           = $browserContext->getWebDriver();
                    $divergenceDelayTime = (float) random_int(3, 10);

                    // note: an exception will not be bubbled to the parent context
                    // (need an explicit rejection handler here, in case of more advanced logic).
                    return $webDriver
                        ->wait($divergenceDelayTime)
                        ->then(fn () => $linkIdentifier)
                    ;
                }
            )
        ;

        $pageAccessPromise = $linkIdentifierPromise
            ->then(fn (array $linkIdentifier) => $this->doPageTransition($browserContext, $linkIdentifier))
            // ensure a page is fully loaded before we can analyse its contents.
            ->then(fn () => $this->ensurePageLoaded($browserContext))
            ->then(
                null,
                function (Throwable $rejectionReason) {
                    throw new RuntimeException(
                        'Unable to find (and open) an item page (item searcher).',
                        0,
                        $rejectionReason
                    );
                }
            )
        ;

        return $pageAccessPromise;
    }

    /**
     * Returns a promise that resolves to a data structure, representing an internal handle of the page element, which
     * is used for item search
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<array>
     */
    private function prepareSearchBar(BrowserContext $browserContext): PromiseInterface
    {
        $searchBarReadyPromise = $this->searchBarLocator
            // resolving search bar element / navigating to the appropriate search page.
            ->locateSearchBar($browserContext)
            // focusing the input.
            ->then(
                function (array $searchBarIdentifier) use ($browserContext) {
                    return $this
                        ->focusSearchBar($browserContext, $searchBarIdentifier)
                        // forwarding a search bar handle to the next async handler.
                        ->then(fn () => $searchBarIdentifier)
                    ;
                }
            )
        ;

        return $searchBarReadyPromise;
    }

    /**
     * Returns a promise that will be resolved when the search input becomes active
     *
     * @param BrowserContext $browserContext      Holds browser state and a driver reference to perform actions
     * @param array          $searchBarIdentifier An internal WebDriver handle for the search input
     *
     * @return PromiseInterface<null>
     */
    private function focusSearchBar(BrowserContext $browserContext, array $searchBarIdentifier): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $divergenceOffsetX = random_int(0, 20);
        $divergenceOffsetY = random_int(0, 5);

        $clickConfirmationPromise = $webDriver
            // moving a mouse pointer to the search bar.
            ->mouseMove(
                $sessionIdentifier,
                $divergenceOffsetX,
                $divergenceOffsetY,
                100,
                $searchBarIdentifier
            )
            // sending a left click action.
            ->then(fn () => $webDriver->mouseLeftClick($sessionIdentifier))
        ;

        return $clickConfirmationPromise;
    }

    /**
     * Returns a promise that will be resolved when the remove WebDriver service confirms a text type operation for the
     * search input
     *
     * @param BrowserContext $browserContext      Holds browser state and a driver reference to perform actions
     * @param array          $searchBarIdentifier An internal WebDriver handle for the search input
     * @param Item           $item                A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<null>
     */
    private function doSearchQuery(
        BrowserContext $browserContext,
        array $searchBarIdentifier,
        Item $item
    ): PromiseInterface {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $itemName   = $item->getName();
        $textToType = mb_strtolower($itemName);

        $textTypePromise = $webDriver->keypressElement($sessionIdentifier, $searchBarIdentifier, $textToType);

        return $textTypePromise;
    }

    /**
     * Returns a promise that resolves to a data structure, representing an internal handle for the item page link,
     * when the remote browser completes search results rendering
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<array>
     */
    private function waitSearchResults(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        $waitingDeferred = new Deferred();

        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $linkXPathQuery = $this->buildLinkXPath($item);

        // todo: decompose
        $conditionMetCallback = function () use ($webDriver, $sessionIdentifier, $linkXPathQuery) {
            // resolving an internal WebDriver handle for the link element.
            $linkIdentifierPromise = $webDriver->getElementIdentifier($sessionIdentifier, $linkXPathQuery);

            // checking visibility state for the link.
            return $linkIdentifierPromise->then(
                function (array $linkIdentifier) use ($webDriver, $sessionIdentifier) {
                    $visibilityStatePromise = $webDriver->getElementVisibility($sessionIdentifier, $linkIdentifier);

                    return $visibilityStatePromise->then(
                        function (bool $isVisible) use ($linkIdentifier) {
                            if (!$isVisible) {
                                // this will force WebDriver to retry visibility check operation.
                                throw new RuntimeException();
                            }

                            // ok, the link becomes visible by this point.
                            // forwarding an internal link handle to the resolving closure.
                            return $linkIdentifier;
                        }
                    );
                }
            );
        };

        $becomeVisiblePromise = $webDriver->waitUntil($conditionMetCallback, $this->ajaxTimeout);

        $becomeVisiblePromise->then(
            function (array $linkIdentifier) use ($waitingDeferred) {
                $waitingDeferred->resolve($linkIdentifier);
            },
            // handling a case, when we don't see search results on the page (client-side errors, etc.).
            function (Throwable $rejectionReason) use ($waitingDeferred) {
                $reason = new RuntimeException('Unable to analyse search results.', 0, $rejectionReason);

                $waitingDeferred->reject($reason);
            }
        );

        $linkIdentifierPromise = $waitingDeferred->promise();

        return $linkIdentifierPromise;
    }

    /**
     * Returns a promise that will be resolved when an item page, from the search results, becomes open (link
     * transition was successfully performed)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param array          $linkIdentifier An internal WebDriver handle for link in the search results
     *
     * @return PromiseInterface<null>
     */
    private function doPageTransition(BrowserContext $browserContext, array $linkIdentifier): PromiseInterface
    {
        // todo: extract to a shared unit (similar to focusSearchBar)

        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $divergenceOffsetX = random_int(0, 20);
        $divergenceOffsetY = random_int(0, 5);

        $clickConfirmationPromise = $webDriver
            ->mouseMove(
                $sessionIdentifier,
                $divergenceOffsetX,
                $divergenceOffsetY,
                100,
                $linkIdentifier
            )
            ->then(fn () => $webDriver->mouseLeftClick($sessionIdentifier))
        ;

        return $clickConfirmationPromise;
    }

    /**
     * Returns a promise that will be resolved when the page becomes completely loaded in the remote browser instance
     * (including client-side scripts and other runtime assets)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function ensurePageLoaded(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $becomeAvailablePromise = $webDriver->waitUntil(
            function () use ($webDriver, $sessionIdentifier) {
                return $webDriver->getElementIdentifier(
                    $sessionIdentifier,
                    '//table[contains(@class, "price-all")]//*[@data-store]'
                );
            },
            $this->ajaxTimeout
        );

        $becomeAvailablePromise = $becomeAvailablePromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException(
                    'Unable to ensure page contents is fully loaded (AJAX timeout?).',
                    0,
                    $rejectionReason
                );
            }
        );

        return $becomeAvailablePromise;
    }

    /**
     * Returns an XPath query string, which will be used to find a link with item's URI in the search results
     *
     * @param Item $item A hardware item DTO with metadata for price retrieving
     *
     * @return string
     */
    private function buildLinkXPath(Item $item): string
    {
        $itemPageUri = $item->getPageUri();

        if (false !== strpos($itemPageUri, '"')) {
            throw new RuntimeException('Unable to prepare an XPath query: incorrect format for the item page URI.');
        }

        $linkXPathQuery = sprintf('//a[@href="%s"]', $itemPageUri);

        return $linkXPathQuery;
    }
}
