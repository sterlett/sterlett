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
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Bridge\React\EventLoop\TimeIssuerInterface;
use Throwable;

/**
 * Opens a remote browser and starts a new browsing session to find hardware prices on the website
 */
class Opener
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
     * Opener constructor.
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
     * Returns a promise that resolves to a browsing context instance, filled with the session-specific information to
     * perform actions in the remote browser
     *
     * @return PromiseInterface<Context>
     */
    public function openBrowser(): PromiseInterface
    {
        $openingDeferred = new Deferred();

        $sessionIdentifierPromise = $this->webDriver->createSession();

        $sessionIdentifierPromise->then(
            function (string $sessionIdentifier) use ($openingDeferred) {
                try {
                    $contextPromise = $this->onSessionIdentifier($sessionIdentifier);

                    $openingDeferred->resolve($contextPromise);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to acquire tab identifiers (command).', 0, $exception);

                    $openingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($openingDeferred) {
                $reason = new RuntimeException('Unable to open a session in the remote browser.', 0, $rejectionReason);

                $openingDeferred->reject($reason);
            }
        );

        $contextPromise = $openingDeferred->promise();

        return $contextPromise;
    }

    /**
     * Sends a tab lookup command and returns a promise of browsing context, which will be created by the resolved
     * session identifier
     *
     * @param string $sessionIdentifier Identifier for the remote browser session to perform actions
     *
     * @return PromiseInterface<Context>
     */
    private function onSessionIdentifier(string $sessionIdentifier): PromiseInterface
    {
        $contextBuildingDeferred = new Deferred();

        $tabIdentifierListPromise = $this->webDriver->getTabIdentifiers($sessionIdentifier);

        $tabIdentifierListPromise->then(
            function (array $tabIdentifiers) use ($contextBuildingDeferred, $sessionIdentifier) {
                try {
                    $context = $this->onSessionAndTabIdentifiers($sessionIdentifier, $tabIdentifiers);

                    $contextBuildingDeferred->resolve($context);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to build a context for browsing session.', 0, $exception);

                    $contextBuildingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($contextBuildingDeferred) {
                $reason = new RuntimeException('Unable to lookup tabs in the remote browser.', 0, $rejectionReason);

                $contextBuildingDeferred->reject($reason);
            }
        );

        $contextPromise = $contextBuildingDeferred->promise();

        return $contextPromise;
    }

    /**
     * Builds and returns a context instance when both session identifier and tab handles are acquired
     *
     * @param string $sessionIdentifier Identifier for the remote browser session to perform actions
     * @param array  $tabIdentifiers    Array of tab identifiers (handles), which are opened in the remote browser
     *
     * @return Context
     */
    private function onSessionAndTabIdentifiers(string $sessionIdentifier, array $tabIdentifiers): Context
    {
        $context = new Context();
        $context->setBrowsingThread($this->browsingThread);
        $context->setWebDriver($this->webDriver);
        $context->setHubSession($sessionIdentifier);
        $context->setTabIdentifiers($tabIdentifiers);

        return $context;
    }
}
