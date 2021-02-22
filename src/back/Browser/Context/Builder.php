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

namespace Sterlett\Browser\Context;

use Itnelo\React\WebDriver\WebDriverInterface;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Sterlett\Browser\Context as BrowserContext;

/**
 * Builds a context object to provide a WebDriver reference, a bridge to the event loop implementation (thread) and
 * other levers for the scraping session
 */
class Builder
{
    /**
     * Allocates execution time in the centralized event loop for browsing actions
     *
     * @var TimeIssuerInterface
     */
    private TimeIssuerInterface $browsingThread;

    /**
     * Manipulates a remote browser instance asynchronously, using Selenium Grid (hub) API
     *
     * @var WebDriverInterface
     */
    private WebDriverInterface $webDriver;

    /**
     * Builder constructor.
     *
     * @param TimeIssuerInterface $browsingThread Allocates execution time in the centralized event loop
     * @param WebDriverInterface  $webDriver      Manipulates a remote browser instance
     */
    public function __construct(TimeIssuerInterface $browsingThread, WebDriverInterface $webDriver)
    {
        $this->browsingThread = $browsingThread;
        $this->webDriver      = $webDriver;
    }

    /**
     * Builds and returns a context instance, using the configured services
     *
     * @return BrowserContext
     */
    public function getContext(): BrowserContext
    {
        $context = new BrowserContext();
        $context->setBrowsingThread($this->browsingThread);
        $context->setWebDriver($this->webDriver);

        return $context;
    }
}
