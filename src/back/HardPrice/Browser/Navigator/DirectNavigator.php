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

namespace Sterlett\HardPrice\Browser\Navigator;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Browser\Tab\Actualizer as TabActualizer;
use Sterlett\HardPrice\Browser\NavigatorInterface;
use Throwable;

/**
 * Accessing a website with prices directly, without any referrers
 *
 * todo: extract duplicate logic to a separate code unit (see ReferrerNavigator)
 * todo: make sure to open it in the second tab (see PriceAccumulator::openBrowserTab)
 */
class DirectNavigator implements NavigatorInterface
{
    /**
     * Updates a list with available browser tabs (window handles) for the browser context
     *
     * @var TabActualizer
     */
    private TabActualizer $tabActualizer;

    /**
     * An absolute URL to the target website
     *
     * @var string
     */
    private string $websiteUri;

    /**
     * DirectNavigator constructor.
     *
     * @param TabActualizer $tabActualizer Updates a list with available browser tabs (window handles)
     * @param string        $websiteUri    An absolute URL to the target website
     */
    public function __construct(TabActualizer $tabActualizer, string $websiteUri)
    {
        $this->tabActualizer = $tabActualizer;
        $this->websiteUri    = $websiteUri;
    }

    /**
     * {@inheritDoc}
     */
    public function navigate(BrowserContext $browserContext): PromiseInterface
    {
        $browsingThread = $browserContext->getBrowsingThread();

        $navigationDeferred = new Deferred();

        $siteAccessPromise = $browsingThread
            // acquiring a time frame in the shared event loop.
            ->getTime()
            // opening a website.
            ->then(fn () => $this->openWebsite($browserContext))
        ;

        // executing cleanup routines.
        $siteAccessPromise
            ->then(fn () => $this->actualizeContext($browserContext))
            // handling errors and releasing a browsing thread lock.
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
     * Sends a command to open a website in the current browser tab
     *
     * @param BrowserContext $browserContext Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<null>
     */
    private function openWebsite(BrowserContext $browserContext): PromiseInterface
    {
        $webDriver         = $browserContext->getWebDriver();
        $sessionIdentifier = $browserContext->getHubSession();

        $websitePromise = $webDriver->openUri($sessionIdentifier, $this->websiteUri);

        return $websitePromise;
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
