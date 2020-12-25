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
use Sterlett\HardPrice\Item\Parser as ItemParser;
use Throwable;

/**
 * Opens a page with hardware items in the remove browser and saves them for browsing context
 */
class ItemReader
{
    /**
     * Transforms hardware items data from the raw format to the iterable list of normalized values (DTOs)
     *
     * @var ItemParser
     */
    private ItemParser $itemParser;

    /**
     * ItemReader constructor.
     *
     * @param ItemParser $itemParser Transforms hardware data from the raw format to the list of DTOs
     */
    public function __construct(ItemParser $itemParser)
    {
        $this->itemParser = $itemParser;
    }

    /**
     * Returns a promise that resolves to a list of hardware items, found on the website using a remote browser.
     *
     * The resulting collection represents a Traversable<Item> or Item[].
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<iterable>
     */
    public function readItems(BrowserContext $browserContext): PromiseInterface
    {
        $browsingThread = $browserContext->getBrowsingThread();

        $lookupDeferred = new Deferred();

        $tabReadyPromise = $browsingThread
            // acquiring a time frame in the shared event loop.
            ->getTime()
            // opening a tab in the remote browser, where we will analyse a web resource with hardware list.
            ->then(fn () => $this->openBrowserTab($browserContext))
        ;

        $resourceAccessPromise = $tabReadyPromise
            // accessing a page with hardware list.
            ->then(fn () => $this->accessResource($browserContext))
        ;

        $resourceAccessPromise
            // loading a page source.
            ->then(fn () => $this->readSourceCode($browserContext))
            // extracting a hardware list from the page.
            ->then(fn (string $rawData) => $this->itemParser->parse($rawData))
            // handling errors and releasing a thread lock.
            ->then(
                function (iterable $hardwareItems) use ($browsingThread, $lookupDeferred) {
                    $browsingThread->release();

                    $lookupDeferred->resolve($hardwareItems);
                },
                function (Throwable $rejectionReason) use ($browsingThread, $lookupDeferred) {
                    $browsingThread->release();

                    $reason = new RuntimeException('Unable to lookup hardware items.', 0, $rejectionReason);
                    $lookupDeferred->reject($reason);
                }
            )
        ;

        $itemListPromise = $lookupDeferred->promise();

        return $itemListPromise;
    }

    /**
     * Sends a command to switch browser tab to one that maintains a source for hardware list building
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function openBrowserTab(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();
        $tabIdentifiers    = $browserContext->getTabIdentifiers();

        $activeTabIdentifierPromise = $webDriver->getActiveTabIdentifier($sessionIdentifier);

        $activeTabPromise = $activeTabIdentifierPromise->then(
            function (string $activeTabIdentifier) use ($webDriver, $sessionIdentifier, $tabIdentifiers) {
                if (!isset($tabIdentifiers[0])) {
                    throw new RuntimeException('Unable to open a browser tab (index: 0).');
                }

                // assigning tab 0 for hardware items.
                // will switch to this tab (unless it is already focused).
                if ($tabIdentifiers[0] === $activeTabIdentifier) {
                    return null;
                }

                return $webDriver->setActiveTab($sessionIdentifier, $tabIdentifiers[0]);
            }
        );

        return $activeTabPromise;
    }

    /**
     * Sends a command to open a web resource with hardware items (if not already opened in the active tab)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function accessResource(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $uriDesired = 'https://hardprice.ru/media/data/c/cpu.json';

        // reading uri from the current (focused) tab.
        $currentUriPromise = $webDriver->getCurrentUri($sessionIdentifier);

        $resourceAccessPromise = $currentUriPromise->then(
            function (string $uriCurrent) use ($webDriver, $sessionIdentifier, $uriDesired) {
                // this is rather a precaution to not disturbing an "angry dog" too much.
                if ($uriCurrent === $uriDesired) {
                    return null;
                }

                return $webDriver->openUri($sessionIdentifier, $uriDesired);
            }
        );

        return $resourceAccessPromise;
    }

    /**
     * Extracts contents of the web resource as a raw string (to fill a list of DTOs)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<string>
     */
    private function readSourceCode(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $sourceCodePromise = $webDriver->getSource($sessionIdentifier);

        $rawDataPromise = $sourceCodePromise->then(
            function (string $sourceCode) {
                // todo: unbind from json-only, hardcoded approach
                $jsonStartIndex = strpos($sourceCode, '[');
                $jsonEndIndex   = strrpos($sourceCode, ']') - $jsonStartIndex + 1;

                $rawData = substr($sourceCode, $jsonStartIndex, $jsonEndIndex);

                return $rawData;
            }
        );

        return $rawDataPromise;
    }
}
