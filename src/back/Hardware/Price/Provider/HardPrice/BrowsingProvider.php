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

namespace Sterlett\Hardware\Price\Provider\HardPrice;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventDispatcher;
use Sterlett\Browser\Cleaner as BrowserCleaner;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Browser\OpenerInterface as BrowserOpenerInterface;
use Sterlett\Event\ShutdownStartedEvent;
use Sterlett\HardPrice\Browser\ItemReader;
use Sterlett\HardPrice\Browser\NavigatorInterface;
use Sterlett\HardPrice\Browser\PriceAccumulator;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;
use function React\Promise\resolve;

/**
 * Gen 3 algorithm for price data retrieving from the HardPrice website.
 *
 * Emulates user behavior while traversing site pages using browser API (XVFB mode).
 */
class BrowsingProvider implements ProviderInterface
{
    /**
     * Dispatches application-level events using future tick queue of the event loop
     *
     * @var DeferredEventDispatcher
     */
    private DeferredEventDispatcher $eventDispatcher;

    /**
     * Opens a remote browser and starts a new browsing session to find hardware prices on the website
     *
     * @var BrowserOpenerInterface
     */
    private BrowserOpenerInterface $browserOpener;

    /**
     * Stops a remote browser session and cleans all related resources
     *
     * @var BrowserCleaner
     */
    private BrowserCleaner $browserCleaner;

    /**
     * Opens HardPrice website in the remote browser tab
     *
     * @var NavigatorInterface
     */
    private NavigatorInterface $siteNavigator;

    /**
     * Opens a page with hardware items in the remove browser and saves them for browsing context
     *
     * @var ItemReader
     */
    private ItemReader $itemReader;

    /**
     * Starts price accumulating routine for hardware items from the HardPrice website using a remote browser instance
     *
     * @var PriceAccumulator
     */
    private PriceAccumulator $priceAccumulator;

    /**
     * BrowsingProvider constructor.
     *
     * @param DeferredEventDispatcher $eventDispatcher  Dispatches application-level events using the future tick queue
     * @param BrowserOpenerInterface  $browserOpener    Opens a remote browser and starts a new browsing session
     * @param BrowserCleaner          $browserCleaner   Stops a remote browser session and cleans all related resources
     * @param NavigatorInterface      $siteNavigator    Opens HardPrice website in the remote browser tab
     * @param ItemReader              $itemReader       Opens a page with hardware items in the remove browser tab
     * @param PriceAccumulator        $priceAccumulator Starts price accumulating routine for hardware items
     */
    public function __construct(
        DeferredEventDispatcher $eventDispatcher,
        BrowserOpenerInterface $browserOpener,
        BrowserCleaner $browserCleaner,
        NavigatorInterface $siteNavigator,
        ItemReader $itemReader,
        PriceAccumulator $priceAccumulator
    ) {
        $this->eventDispatcher  = $eventDispatcher;
        $this->browserOpener    = $browserOpener;
        $this->browserCleaner   = $browserCleaner;
        $this->siteNavigator    = $siteNavigator;
        $this->itemReader       = $itemReader;
        $this->priceAccumulator = $priceAccumulator;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        // stage 1: opening a browser session.
        $browserReadyPromise = $this->browserOpener->openBrowser();

        $browserReadyPromise->then(
            function (BrowserContext $browserContext) use ($retrievingDeferred) {
                try {
                    $this->onBrowserReady($retrievingDeferred, $browserContext);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException(
                        'Unable to retrieve prices (site navigation is not started).',
                        0,
                        $exception
                    );

                    $retrievingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices (browser session).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $priceListPromise = $retrievingDeferred->promise();

        return $priceListPromise;
    }

    /**
     * Runs when the remote browser is successfully setted up and ready to execute navigation commands
     *
     * @param Deferred       $retrievingDeferred Represents the price retrieving process itself
     * @param BrowserContext $browserContext     Holds browser state and a driver reference to perform actions
     *
     * @return void
     */
    private function onBrowserReady(Deferred $retrievingDeferred, BrowserContext $browserContext): void
    {
        // stage 2: navigating to the website.
        $tabIdentifiers = $browserContext->getTabIdentifiers();

        // looks like it has already been made (reusing an existing session), skipping.
        if (count($tabIdentifiers) > 1) {
            $this->onSiteNavigation($retrievingDeferred, $browserContext);

            return;
        }

        // registering a callback to gracefully remove driver session on application shutdown.
        $this->eventDispatcher->addListener(
            ShutdownStartedEvent::NAME,
            function () use ($browserContext) {
                return $this->releaseDriver($browserContext);
            }
        );

        // will run a navigation query otherwise.
        $navigationPromise = $this->siteNavigator->navigate($browserContext);

        $navigationPromise->then(
            function () use ($retrievingDeferred, $browserContext) {
                try {
                    $this->onSiteNavigation($retrievingDeferred, $browserContext);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException(
                        'Unable to retrieve prices (item search is not started).',
                        0,
                        $exception
                    );

                    $retrievingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices (site navigation).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );
    }

    /**
     * Runs when the website becomes open in the remote browser, to find a list with hardware items for price lookup
     *
     * @param Deferred       $retrievingDeferred Represents the price retrieving process itself
     * @param BrowserContext $browserContext     Holds browser state and a driver reference to perform actions
     *
     * @return void
     */
    private function onSiteNavigation(Deferred $retrievingDeferred, BrowserContext $browserContext): void
    {
        // stage 3: searching hardware items.
        $itemListPromise = $this->itemReader->readItems($browserContext);

        $itemListPromise->then(
            function (iterable $hardwareItems) use ($retrievingDeferred, $browserContext) {
                try {
                    $this->onItemsFound($retrievingDeferred, $browserContext, $hardwareItems);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException(
                        'Unable to retrieve prices (accumulator is not started).',
                        0,
                        $exception
                    );

                    $retrievingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices (item search).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );
    }

    /**
     * Runs when the collection of hardware items is acquired, to start price accumulating routine
     *
     * @param Deferred       $retrievingDeferred Represents the price retrieving process itself
     * @param BrowserContext $browserContext     Holds browser state and a driver reference to perform actions
     * @param iterable       $hardwareItems      A collection of hardware items for which prices will be accumulated
     *
     * @return void
     */
    private function onItemsFound(
        Deferred $retrievingDeferred,
        BrowserContext $browserContext,
        iterable $hardwareItems
    ): void {
        // stage 4: browsing item pages.
        $priceListPromise = $this->priceAccumulator->accumulatePrices($browserContext, $hardwareItems);

        $priceListPromise->then(
            function (iterable $hardwarePrices) use ($retrievingDeferred, $browserContext) {
                try {
                    $this->onPricesAccumulated($retrievingDeferred, $browserContext, $hardwarePrices);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to retrieve prices (collecting failure).', 0, $exception);

                    $retrievingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices (accumulator).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );
    }

    /**
     * Runs when a collection of hardware prices is successfully accumulated and provider is about to finish its work
     *
     * @param Deferred       $retrievingDeferred Represents the price retrieving process itself
     * @param BrowserContext $browserContext     Holds browser state and a driver reference to perform actions
     * @param iterable       $hardwarePrices     A collection of hardware prices from the different stores
     *
     * @return void
     */
    private function onPricesAccumulated(
        Deferred $retrievingDeferred,
        BrowserContext $browserContext,
        iterable $hardwarePrices
    ): void {
        // stage 5: closing browser session.
        // we only close browsing session and cleaning up the browser at this stage.
        // it is OK to not clean after each reject, because we can still reuse the same session (browser tabs, etc.)
        // again with no consequences (except excessive mem peaks at some point, which may be affordable).
        // See browser context option 'cleaner.is_enabled' to find more information.

        $this
            // releasing driver resources.
            ->releaseDriver($browserContext)
            // forwarding a result of the price retrieving operation (always: for both resolved/rejected cases).
            ->then(
                fn () => $retrievingDeferred->resolve($hardwarePrices),
                fn () => $retrievingDeferred->resolve($hardwarePrices)
            )
        ;
    }

    /**
     * Executes cleanup operations for the webdriver (and other utilized services). Returns a promise that will be
     * resolved when the related services are freed.
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>|null
     */
    private function releaseDriver(BrowserContext $browserContext): ?PromiseInterface
    {
        $browserOptions   = $browserContext->getOptions();
        $isCleanerEnabled = $browserOptions['cleaner']['is_enabled'];

        if (!$isCleanerEnabled) {
            return resolve(null);
        }

        return $this->browserCleaner->cleanBrowser($browserContext);
    }
}
