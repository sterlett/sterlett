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
use Throwable;
use Traversable;
use function React\Promise\reduce;

/**
 * Starts price accumulating routine for hardware items from the HardPrice website using a remote browser instance
 */
class PriceAccumulator
{
    /**
     * Performs random actions in the remote browser to confuse some website protection systems, which detects browser
     * automation
     *
     * @var Divergent
     */
    private Divergent $divergent;

    /**
     * Performs actions in the remove browser to find a page with hardware item
     *
     * @var ItemSearcher
     */
    private ItemSearcher $itemSearcher;

    /**
     * Opens a page with item prices in the remove browser and reads its contents
     *
     * @var PriceReader
     */
    private PriceReader $priceReader;

    /**
     * PriceAccumulator constructor.
     *
     * @param Divergent    $divergent    Performs random actions in the remote browser (anti-automation bypass)
     * @param ItemSearcher $itemSearcher Performs actions in the remove browser to find a page with hardware item
     * @param PriceReader  $priceReader  Reads a page with item prices from the remove browser tab
     */
    public function __construct(Divergent $divergent, ItemSearcher $itemSearcher, PriceReader $priceReader)
    {
        $this->divergent    = $divergent;
        $this->itemSearcher = $itemSearcher;
        $this->priceReader  = $priceReader;
    }

    /**
     * Returns a promise that resolves to a collection of DTOs with price information for the given hardware items.
     *
     * Element of the resulting collection is a Traversable<Price> or a Price[].
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param iterable       $hardwareItems  A collection of hardware items for which prices will be accumulated
     *
     * @return PromiseInterface<iterable>
     */
    public function accumulatePrices(BrowserContext $browserContext, iterable $hardwareItems): PromiseInterface
    {
        $accumulatingDeferred = new Deferred();

        // slicing the entire execution time into small chunks.
        $accumulatingIterations = $this->splitExecutionTime($browserContext, $hardwareItems);

        $concurrentExecutionPromise = reduce(
            [...$accumulatingIterations],
            // reduce function, to merge results from each iteration.
            function (array $priceList, iterable $itemPrices, int $itemIndex, int $itemCountTotal) {
                $priceList[] = $itemPrices;

                return $priceList;
            },
            // initial value for the reduce container: $priceList.
            []
        );

        $concurrentExecutionPromise->then(
            function (array $priceList) use ($accumulatingDeferred) {
                $accumulatingDeferred->resolve($priceList);
            },
            function (Throwable $rejectionReason) use ($accumulatingDeferred) {
                $reason = new RuntimeException('Unable to accumulate hardware prices.', 0, $rejectionReason);

                $accumulatingDeferred->reject($reason);
            }
        );

        $priceListMergedPromise = $accumulatingDeferred->promise();

        return $priceListMergedPromise;
    }

    /**
     * Returns a collection of promises, where each one represents a set of actions in the remote browser, to retrieve
     * prices for a single hardware item
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param iterable       $hardwareItems  A collection of hardware items (1 item = 1 iteration promise)
     *
     * @return Traversable<PromiseInterface>|PromiseInterface[]
     */
    private function splitExecutionTime(BrowserContext $browserContext, iterable $hardwareItems): iterable
    {
        foreach ($hardwareItems as $item) {
            $accumulatingIteration = $this->findPrices($browserContext, $item);

            yield $accumulatingIteration;
        }
    }

    /**
     * Returns a promise that resolves to a price list for the given hardware item
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A single hardware item, to make a price list resolving promise
     *
     * @return PromiseInterface<iterable>
     */
    private function findPrices(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        $browsingThread = $browserContext->getBrowsingThread();

        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $tabReadyPromise = $browsingThread
            // acquiring a time frame in the shared event loop.
            ->getTime()
            // opening an appropriate tab in the remote browser.
            ->then(fn () => $this->openBrowserTab($browserContext))
            // protecting existence (as long as such protection does not conflict with the First or Second Law).
            ->then(fn () => $this->divergent->randomAction($webDriver, $sessionIdentifier))
            // applying a delay.
            ->then(fn () => $webDriver->wait(5.0))
        ;

        $pageAccessPromise = $tabReadyPromise
            // opening a page with hardware item.
            ->then(fn () => $this->itemSearcher->searchItem($browserContext, $item))
        ;

        $priceListPromise = $pageAccessPromise
            // extracting item prices.
            ->then(fn () => $this->priceReader->readPrices($browserContext, $item))
            // releasing a thread lock / handling errors.
            ->then(
                function (iterable $hardwarePrices) use ($browsingThread) {
                    $browsingThread->release();

                    return $hardwarePrices;
                },
                function (Throwable $rejectionReason) use ($browsingThread) {
                    $browsingThread->release();

                    throw new RuntimeException('Unable to find a price list for an item.', 0, $rejectionReason);
                }
            )
        ;

        return $priceListPromise;
    }

    /**
     * Sends a command to switch browser tab to one that maintains a page with hardware information
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function openBrowserTab(BrowserContext $browserContext): PromiseInterface
    {
        // todo: extract to a separate service

        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();
        $tabIdentifiers    = $browserContext->getTabIdentifiers();

        $activeTabIdentifierPromise = $webDriver->getActiveTabIdentifier($sessionIdentifier);

        $activeTabPromise = $activeTabIdentifierPromise->then(
            function (string $activeTabIdentifier) use ($webDriver, $sessionIdentifier, $tabIdentifiers) {
                if (!isset($tabIdentifiers[1])) {
                    throw new RuntimeException('Unable to open a browser tab (index: 1).');
                }

                // assigning tab 1 for hardware item information.
                // will switch to this tab (unless it is already focused).
                if ($tabIdentifiers[1] === $activeTabIdentifier) {
                    return null;
                }

                return $webDriver->setActiveTab($sessionIdentifier, $tabIdentifiers[1]);
            }
        );

        return $activeTabPromise;
    }
}
