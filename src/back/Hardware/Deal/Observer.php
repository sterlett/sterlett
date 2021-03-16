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

namespace Sterlett\Hardware\Deal;

use ArrayIterator;
use React\Promise\PromiseInterface;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventDispatcher;
use Sterlett\Event\DealsSuggestedEvent;

/**
 * Monitors hardware prices from the different sellers and suggests best deals, in terms of Price/Performance
 */
class Observer
{
    /**
     * Executes analytic queries
     *
     * @var Analyser
     */
    private Analyser $dealAnalyser;

    /**
     * Dispatches application-level events using a future tick queue of the shared event loop
     *
     * @var DeferredEventDispatcher
     */
    private DeferredEventDispatcher $eventDispatcher;

    /**
     * A set of [from, to] benchmark ranges, to measure hardware ranks
     *
     * @var array
     */
    private array $dealRankings;

    /**
     * A number of price records (best deals) for a single rank
     *
     * @var int
     */
    private int $dealsPerRank;

    /**
     * Observer constructor.
     *
     * @param Analyser                $dealAnalyser    Executes analytic queries
     * @param DeferredEventDispatcher $eventDispatcher Dispatches application-level events using a future tick queue
     * @param array                   $dealRankings    A set of [from, to] benchmark ranges, to measure hardware ranks
     * @param int                     $dealsPerRank    A number of price records (best deals) for a single rank
     */
    public function __construct(
        Analyser $dealAnalyser,
        DeferredEventDispatcher $eventDispatcher,
        array $dealRankings,
        int $dealsPerRank
    ) {
        $this->dealAnalyser    = $dealAnalyser;
        $this->eventDispatcher = $eventDispatcher;
        $this->dealRankings    = $dealRankings;
        $this->dealsPerRank    = $dealsPerRank;
    }

    /**
     * Returns a promise that will be resolved when the service completes its deal analysis using available
     * hardware prices and benchmark values.
     *
     * The retval is an event dispatching promise and must be resolved by the responsible async listener or the
     * centralized dispatcher (default).
     *
     * @return PromiseInterface<null>
     */
    public function suggestDeals(): PromiseInterface
    {
        // slicing by item name and seller for each category.
        $analyticsPromise = $this->dealAnalyser->slidingWindowAll($this->dealRankings);

        $dealListTransferPromise = $analyticsPromise
            // taking top N records from the data slice.
            ->then(fn (array $slidingWindows) => $this->pickDeals($slidingWindows, $this->dealsPerRank))
            // dispatching a transfer event.
            ->then(fn (array $deals) => $this->transferDeals($deals))
        ;

        return $dealListTransferPromise;
    }

    /**
     * Will examine a given sliding window with price data for the configured benchmark range and return a slice with
     * best deals
     *
     * @param array $slidingWindows A price data from the local storage
     * @param int   $dealCount      How many deals to take from the source data
     *
     * @return array
     */
    private function pickDeals(array $slidingWindows, int $dealCount): array
    {
        $windowIterator = new ArrayIterator($slidingWindows);
        $windowIterator->rewind();

        $deals           = [];
        $benchmarkRanges = $this->dealRankings;

        foreach ($benchmarkRanges as $benchmarkRange) {
            if (!$windowIterator->valid()) {
                break;
            }

            $benchmarkFrom = $benchmarkRange['from'];
            $benchmarkTo   = $benchmarkRange['to'];

            $slidingWindow = $windowIterator->current();

            $dealSlice = $this->sliceDeals($benchmarkFrom, $benchmarkTo, $slidingWindow, $dealCount);

            $deals = array_merge($deals, $dealSlice);

            $windowIterator->next();
        }

        return $deals;
    }

    /**
     * Packs and returns a data slice with best deals from the available price data
     *
     * @param float    $benchmarkFrom The lower boundary from the benchmark range of interest
     * @param float    $benchmarkTo   The upper boundary from the benchmark range of interest
     * @param iterable $slidingWindow A set of price records from the local storage
     * @param int      $sliceLength   A desired size for the deal slice
     *
     * @return array
     */
    private function sliceDeals(
        float $benchmarkFrom,
        float $benchmarkTo,
        iterable $slidingWindow,
        int $sliceLength
    ): array {
        $windowRewindable = [...$slidingWindow];

        $dealSlice = array_slice($windowRewindable, 0, $sliceLength);

        $rankingKey = sprintf('%s-%s', $benchmarkFrom, $benchmarkTo);

        return [$rankingKey => $dealSlice];
    }

    /**
     * Returns a promise that will be resolved when the deal data transfer is complete
     *
     * @param array $deals A slice with the best hardware deals from the analyser service
     *
     * @return PromiseInterface<null>
     */
    private function transferDeals(array $deals): PromiseInterface
    {
        $dealsSuggestedEvent = new DealsSuggestedEvent();
        $dealsSuggestedEvent->setDeals($deals);

        $this->eventDispatcher->dispatch($dealsSuggestedEvent, DealsSuggestedEvent::NAME);

        $eventPropagationPromise = $dealsSuggestedEvent->getPromise();

        return $eventPropagationPromise;
    }
}
