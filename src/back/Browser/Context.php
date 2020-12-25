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

namespace Sterlett\Browser;

use Itnelo\React\WebDriver\WebDriverInterface;
use LogicException;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;

/**
 * A shared storage that holds state of browsing process (gen 3 algorithm)
 */
class Context
{
    /**
     * Acquires time for browsing operation from the centralized event loop
     *
     * @var TimeIssuerInterface|null
     */
    private ?TimeIssuerInterface $browsingThread;

    /**
     * Executes commands in the Selenium hub to manipulate a remote browser instance
     *
     * @var WebDriverInterface|null
     */
    private ?WebDriverInterface $webDriver;

    /**
     * Selenium hub session to execute commands in the remote browser
     *
     * @var string|null
     */
    private ?string $hubSession;

    /**
     * A list of tab identifiers, which are opened by the remote browser instance
     *
     * @var string[]
     */
    private array $tabIdentifiers;

    /**
     * Context constructor.
     */
    public function __construct()
    {
        $this->browsingThread = null;
        $this->webDriver      = null;
        $this->hubSession     = null;
        $this->tabIdentifiers = [];
    }

    /**
     * Returns a thread instance for the browsing context
     *
     * @return TimeIssuerInterface
     */
    public function getBrowsingThread(): TimeIssuerInterface
    {
        if (!$this->browsingThread instanceof TimeIssuerInterface) {
            throw new LogicException("'browsingThread' must be explicitly set to use a browsing context.");
        }

        return $this->browsingThread;
    }

    /**
     * Sets a thread instance for the browsing context
     *
     * @param TimeIssuerInterface $browsingThread Acquires time for browsing operation from the centralized event loop
     *
     * @return void
     */
    public function setBrowsingThread(TimeIssuerInterface $browsingThread): void
    {
        $this->browsingThread = $browsingThread;
    }

    /**
     * Returns a driver instance that executes commands in the Selenium hub
     *
     * @return WebDriverInterface
     */
    public function getWebDriver(): WebDriverInterface
    {
        if (!$this->webDriver instanceof WebDriverInterface) {
            throw new LogicException("'webDriver' must be explicitly set to use a browsing context.");
        }

        return $this->webDriver;
    }

    /**
     * Sets a driver instance that executes commands in the Selenium hub
     *
     * @param WebDriverInterface $webDriver A driver instance that executes commands in the Selenium hub
     *
     * @return void
     */
    public function setWebDriver(WebDriverInterface $webDriver): void
    {
        $this->webDriver = $webDriver;
    }

    /**
     * Returns a Selenium hub session to execute commands in the remote browser
     *
     * @return string
     */
    public function getHubSession(): string
    {
        if (!is_string($this->hubSession)) {
            throw new LogicException("'hubSession' must be explicitly set to use a browsing context.");
        }

        return $this->hubSession;
    }

    /**
     * Sets a Selenium hub session to execute commands in the remote browser
     *
     * @param string $hubSession Selenium hub session to execute commands in the remote browser
     *
     * @return void
     */
    public function setHubSession(string $hubSession): void
    {
        $this->hubSession = $hubSession;
    }

    /**
     * Returns a list of tab identifiers to manipulate remote browser windows
     *
     * @return string[]
     */
    public function getTabIdentifiers(): array
    {
        return $this->tabIdentifiers;
    }

    /**
     * Sets a list of tab identifiers to manipulate remote browser windows
     *
     * @param string[] $tabIdentifiers A list of tab identifiers to manipulate remote browser windows
     *
     * @return void
     */
    public function setTabIdentifiers(array $tabIdentifiers): void
    {
        $this->tabIdentifiers = $tabIdentifiers;
    }
}
