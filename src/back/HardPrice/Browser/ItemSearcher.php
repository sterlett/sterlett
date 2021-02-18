<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020-2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\HardPrice\Browser;

use LogicException;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Bridge\React\Promise\RetryAssistant;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Browser\Refresher;
use Sterlett\Dto\Hardware\Item;
use Sterlett\HardPrice\Browser\ItemSearcher\SearchBarLocator;
use Throwable;
use function React\Promise\resolve;

/**
 * Performs actions in the remove browser to find a page with hardware item information
 */
class ItemSearcher
{
    /**
     * Will try to resolve a promise from the promisor callback, while the configured retry counter is valid
     *
     * @var RetryAssistant
     */
    private RetryAssistant $retryAssistant;

    /**
     * Resets state of the page from the currently active browser tab using a given context
     *
     * @var Refresher
     */
    private Refresher $tabRefresher;

    /**
     * Finds an element on the page, which is suited for item search
     *
     * @var SearchBarLocator
     */
    private SearchBarLocator $searchBarLocator;

    /**
     * Timeout for waitUntil condition checks, which ensures all page data is loaded
     *
     * @var float
     */
    private float $ajaxTimeout;

    /**
     * Frequency for waitUntil checks
     *
     * @var float
     */
    private float $checkFrequency;

    /**
     * ItemSearcher constructor.
     *
     * @param RetryAssistant   $retryAssistant   Will try to resolve a promise, while the retry counter is valid
     * @param Refresher        $tabRefresher     Resets state of the page from the currently active browser tab
     * @param SearchBarLocator $searchBarLocator Finds an element on the page, which is suited for item search
     * @param float            $ajaxTimeout      Timeout for waitUntil condition checks (e.g. 30.0)
     * @param float            $checkFrequency   Frequency for waitUntil checks (e.g. 0.5)
     */
    public function __construct(
        RetryAssistant $retryAssistant,
        Refresher $tabRefresher,
        SearchBarLocator $searchBarLocator,
        float $ajaxTimeout,
        float $checkFrequency
    ) {
        $this->retryAssistant   = $retryAssistant;
        $this->tabRefresher     = $tabRefresher;
        $this->searchBarLocator = $searchBarLocator;

        $this->ajaxTimeout    = max(0.5, $ajaxTimeout);
        $this->checkFrequency = max(0.1, $checkFrequency);
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
        $webDriver = $browserContext->getWebDriver();

        $linkIdentifierPromise = $this->findItemLink($browserContext, $item);

        $pageAccessPromise = $linkIdentifierPromise
            // initiating a page transition.
            ->then(fn (array $linkIdentifier) => $this->doPageTransition($browserContext, $linkIdentifier))
            // applying a delay.
            ->then(fn () => $webDriver->wait(5.0))
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
     * Returns a promise that resolves to an array, representing an internal WebDriver handle for the link element
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<array>
     */
    private function findItemLink(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        // retry logic, to handle "connection closed unexpectedly" while polling chromium state in some situations.
        $linkIdentifierPromise = $this->retryAssistant->retry(
            function (int $retryCountCurrent) use ($browserContext, $item) {
                if ($retryCountCurrent > 0) {
                    $freshTabPromise = $this->tabRefresher->refreshTab($browserContext);
                } else {
                    $freshTabPromise = resolve(null);
                }

                return $freshTabPromise->then(fn () => $this->doFindItemLink($browserContext, $item));
            }
        );

        return $linkIdentifierPromise;
    }

    /**
     * Finds a link to the item page in current browser tab, to perform a natural (and trustful) transition between
     * website pages. Returns a promise that will be resolved to an array, representing an internal WebDriver handle
     * for the desired element.
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<array>
     */
    private function doFindItemLink(BrowserContext $browserContext, Item $item): PromiseInterface
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

                    return $webDriver
                        ->wait($divergenceDelayTime)
                        ->then(fn () => $linkIdentifier)
                    ;
                }
            )
        ;

        return $linkIdentifierPromise;
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
                                throw new LogicException();
                            }

                            // ok, the link becomes visible by this point.
                            // forwarding an internal link handle to the resolving closure.
                            return $linkIdentifier;
                        }
                    );
                }
            );
        };

        $becomeVisiblePromise = $webDriver->waitUntil($conditionMetCallback, $this->ajaxTimeout, $this->checkFrequency);

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
        // retry logic, to handle "connection closed unexpectedly" while polling chromium state in some situations.
        //
        // [1132:1132:0212/133104.602176:VERBOSE1:script_context.cc(273)] Created context:
        //   extension id:           (none)
        //   frame:                  0xc420cd82700
        //   URL:
        //   context_type:           WEB_PAGE
        //   effective extension id: (none)
        //   effective context type: WEB_PAGE
        // [1132:1132:0212/133104.624239:VERBOSE1:script_context.cc(273)] Created context:
        //   extension id:           (none)
        //   frame:                  (nil)
        //   URL:
        //   context_type:           UNSPECIFIED
        //   effective extension id: (none)
        //   effective context type: UNSPECIFIED
        // [1132:1132:0212/133104.633487:VERBOSE1:dispatcher.cc(373)] Num tracked contexts: 1
        // [1040:1040:0212/133104.876625:VERBOSE1:dispatcher.cc(553)] Num tracked contexts: 0

        $becomeAvailablePromise = $this->retryAssistant->retry(
            function (int $retryCountCurrent) use ($browserContext) {
                if ($retryCountCurrent > 0) {
                    // we need to refresh a tab, to clear its state, if this is not a first attempt.
                    $freshTabPromise = $this->tabRefresher->refreshTab($browserContext);
                } else {
                    $freshTabPromise = resolve(null);
                }

                return $freshTabPromise->then(fn () => $this->ensurePageLoadedInternal($browserContext));
            }
        );

        return $becomeAvailablePromise;
    }

    /**
     * An actual logic to ensure a valid page state, which is being protected by the retry assistant
     * (multiple attempts available)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function ensurePageLoadedInternal(BrowserContext $browserContext): PromiseInterface
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
            $this->ajaxTimeout,
            $this->checkFrequency
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
