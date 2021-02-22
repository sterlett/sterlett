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

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Browser\Tab\Actualizer as TabActualizer;
use Throwable;

/**
 * Opens the HardPrice website in the remote browser tab
 */
class SiteNavigator
{
    /**
     * Updates a list with available browser tabs (window handles) for the browser context
     *
     * @var TabActualizer
     */
    private TabActualizer $tabActualizer;

    /**
     * SiteNavigator constructor.
     *
     * @param TabActualizer $tabActualizer Updates a list with available browser tabs (window handles)
     */
    public function __construct(TabActualizer $tabActualizer)
    {
        $this->tabActualizer = $tabActualizer;
    }

    /**
     * Returns a promise that will be resolved when the website becomes open in the remote browser
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    public function navigate(BrowserContext $browserContext): PromiseInterface
    {
        $browsingThread = $browserContext->getBrowsingThread();
        $webDriver      = $browserContext->getWebDriver();

        $navigationDeferred = new Deferred();

        $searchEnginePromise = $browsingThread
            // acquiring a time frame in the shared event loop.
            ->getTime()
            // opening a search engine to make a trustworthy referrer transition.
            ->then(fn () => $this->openSearchEngine($browserContext))
            // applying a delay before we will access search results.
            ->then(fn () => $webDriver->wait(5.0))
        ;

        $siteAccessPromise = $searchEnginePromise
            ->then(fn () => $this->accessSiteByLink($browserContext))
        ;

        // executing cleanup routines.
        $siteAccessPromise
            ->then(fn () => $this->actualizeContext($browserContext))
            // handling errors and releasing a thread lock.
            ->then(
                function () use ($browsingThread, $navigationDeferred) {
                    $browsingThread->release();

                    $navigationDeferred->resolve(null);
                },
                function (Throwable $rejectionReason) use ($browsingThread, $navigationDeferred) {
                    $browsingThread->release();

                    $reason = new RuntimeException('Unable to perform site navigation.', 0, $rejectionReason);
                    $navigationDeferred->reject($reason);
                }
            )
        ;

        $navigationPromise = $navigationDeferred->promise();

        return $navigationPromise;
    }

    /**
     * Sends a command to open a search engine in the current browser tab
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function openSearchEngine(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $searchEnginePromise = $webDriver->openUri(
            $sessionIdentifier,
            'https://google.ru/search?q=hardprice+процессоры'
        );

        return $searchEnginePromise;
    }

    /**
     * Finds a link to the target website in the search engine results and clicks it
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function accessSiteByLink(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $siteAccessPromise = $webDriver
            // acquiring starting point coordinates for mouse move action.
            ->getElementIdentifier($sessionIdentifier, '//span[contains(., "category › cpu")]')
            // moving mouse (an internal pointer) to the link.
            ->then(
                function (array $linkIdentifier) use ($webDriver, $sessionIdentifier) {
                    $divergenceOffsetX = random_int(0, 20);
                    $divergenceOffsetY = random_int(0, 5);

                    return $webDriver->mouseMove(
                        $sessionIdentifier,
                        $divergenceOffsetX,
                        $divergenceOffsetY,
                        100,
                        $linkIdentifier
                    );
                }
            )
            // triggering a click event to open a link in the new browser tab.
            ->then(fn () => $webDriver->mouseLeftClick($sessionIdentifier))
        ;

        return $siteAccessPromise;
    }

    /**
     * Updates browsing context to include a new browser tab identifier, where the website is opened (and, potentially,
     * other changes, which affects a shared browsing state)
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function actualizeContext(BrowserContext $browserContext): PromiseInterface
    {
        $contextUpdatePromise = $this->tabActualizer
            ->actualizeTabs($browserContext)
        ;

        return $contextUpdatePromise;
    }
}
