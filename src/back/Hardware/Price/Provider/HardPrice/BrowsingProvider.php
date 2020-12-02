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

namespace Sterlett\Hardware\Price\Provider\HardPrice;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Cleaner as BrowserCleaner;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Browser\Opener as BrowserOpener;
use Sterlett\HardPrice\Browser\ItemSearcher;
use Sterlett\HardPrice\Browser\PriceAccumulator;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;

/**
 * Gen 3 algorithm for price data retrieving from the HardPrice website.
 *
 * Emulates user behavior while traversing site pages using browser API (XVFB mode).
 *
 * todo: gen 3 algo refactoring (solid/grasp decomposition, code style)
 * todo: move from blocking php-webdriver/webdriver to native async driver (at ReactPHP bridge layer)
 */
class BrowsingProvider implements ProviderInterface
{
    /**
     * @var BrowserOpener
     */
    private BrowserOpener $browserOpener;

    /**
     * @var BrowserCleaner
     */
    private BrowserCleaner $browserCleaner;

    /**
     * @var ItemSearcher
     */
    private ItemSearcher $itemSearcher;

    /**
     * @var PriceAccumulator
     */
    private PriceAccumulator $priceAccumulator;

    public function __construct(
        BrowserOpener $browserOpener,
        BrowserCleaner $browserCleaner,
        ItemSearcher $itemSearcher,
        PriceAccumulator $priceAccumulator
    ) {
        $this->browserOpener    = $browserOpener;
        $this->browserCleaner   = $browserCleaner;
        $this->itemSearcher     = $itemSearcher;
        $this->priceAccumulator = $priceAccumulator;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        // stage 1: opening browser session.
        $browserReadyPromise = $this->browserOpener->openBrowser();

        $browserReadyPromise->then(
            function (BrowserContext $browserContext) use ($retrievingDeferred) {
                try {
                    $this->onBrowserReady($retrievingDeferred, $browserContext);
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
                $reason = new RuntimeException('Unable to retrieve prices (browser session).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $priceListPromise = $retrievingDeferred->promise();

        return $priceListPromise;
    }

    /**
     * @param Deferred       $retrievingDeferred Represents the price retrieving process itself
     * @param BrowserContext $browserContext
     *
     * @return void
     */
    public function onBrowserReady(Deferred $retrievingDeferred, BrowserContext $browserContext): void
    {
        // stage 2: searching hardware items.
        $itemsPromise = $this->itemSearcher->searchItems($browserContext);

        $itemsPromise->then(
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

    public function onItemsFound(
        Deferred $retrievingDeferred,
        BrowserContext $browserContext,
        iterable $hardwareItems
    ): void {
        // stage 3: browsing item pages.
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

    public function onPricesAccumulated(
        Deferred $retrievingDeferred,
        BrowserContext $browserContext,
        iterable $hardwarePrices
    ): void {
        // stage 4: closing browser session.
        // we only close browsing session and cleaning up the browser at this stage.
        // it is OK to not clean after each reject, because we can still reuse the same session (browser tabs, etc.)
        // again with no consequences (except excessive mem peaks, which may be affordable).
        $cleanupPromise = $this->browserCleaner->cleanBrowser($browserContext);

        $cleanupPromise->then(
            function () {
                // todo: log successful cleanup
            },
            function (Throwable $rejectionReason) {
                // todo: log cleanup error
            }
        );

        $retrievingDeferred->resolve($hardwarePrices);
    }
}
