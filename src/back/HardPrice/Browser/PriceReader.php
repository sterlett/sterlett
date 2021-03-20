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

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Dto\Hardware\Item;
use Sterlett\Dto\Hardware\Price;
use Sterlett\HardPrice\Price\Parser as PriceParser;
use Sterlett\Hardware\Price\CollectorInterface;
use Throwable;
use Traversable;

/**
 * Opens a page with item prices in the remove browser and reads its contents
 */
class PriceReader
{
    /**
     * Transforms price data from the raw format to the list of application-level DTOs
     *
     * @var PriceParser
     */
    private PriceParser $priceParser;

    /**
     * Picks price records from the given collection using a filtering condition
     *
     * @var CollectorInterface
     */
    private CollectorInterface $priceCollector;

    /**
     * PriceReader constructor.
     *
     * @param PriceParser        $priceParser    Transforms price data from the raw format to the list of DTOs
     * @param CollectorInterface $priceCollector Picks price records from the collection using a filtering condition
     */
    public function __construct(PriceParser $priceParser, CollectorInterface $priceCollector)
    {
        $this->priceParser    = $priceParser;
        $this->priceCollector = $priceCollector;
    }

    /**
     * Returns a promise that resolves to a collection of prices from the different stores for the given item
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     * @param Item           $item           A hardware item DTO with metadata for price retrieving
     *
     * @return PromiseInterface<iterable>
     */
    public function readPrices(BrowserContext $browserContext, Item $item): PromiseInterface
    {
        $priceListPromise = $this
            // loading a page source.
            ->readSourceCode($browserContext)
            // extracting price data.
            ->then(fn (string $rawData) => $this->parsePrices($item, $rawData))
            ->then(
                null,
                function (Throwable $rejectionReason) {
                    throw new RuntimeException('Unable to read hardware prices from the page.', 0, $rejectionReason);
                }
            )
        ;

        return $priceListPromise;
    }

    /**
     * Extracts hardware price data from the web page as a raw string (to fill a list of DTOs)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<string>
     */
    private function readSourceCode(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $rawDataPromise = $webDriver
            ->getSource($sessionIdentifier)
            // normalizing, to get a cleaner input for parsing.
            ->then(fn (string $sourceCode) => preg_replace('/\s+/', '', $sourceCode))
        ;

        return $rawDataPromise;
    }

    /**
     * Returns a collection of hardware prices, which has been extracted from the item page
     *
     * @param Item   $item    A hardware item DTO with metadata for price retrieving
     * @param string $rawData Item page contents
     *
     * @return Traversable<Price>|Price[]
     */
    private function parsePrices(Item $item, string $rawData): iterable
    {
        $itemName     = $item->getName();
        $itemImageUri = $item->getImageUri();

        $itemPrices = $this->priceParser->parse($rawData);

        $priceListFiltered = $this->priceCollector->collect($itemPrices);

        foreach ($priceListFiltered as $itemPrice) {
            $itemPrice->setHardwareName($itemName);
            $itemPrice->setHardwareImage($itemImageUri);

            yield $itemPrice;
        }
    }
}
