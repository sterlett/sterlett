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

namespace Sterlett\Browser\Opener;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Sterlett\Browser\Context\Builder as ContextBuilder;
use Sterlett\Browser\OpenerInterface;
use Sterlett\Browser\Tab\Actualizer as TabActualizer;
use Throwable;

/**
 * Opens a remote browser and starts a new browsing session
 */
class NewSessionOpener implements OpenerInterface
{
    /**
     * Builds a context object to provide a set of levers (WebDriver reference, loop bridge) for the scraping session
     *
     * @var ContextBuilder
     */
    private ContextBuilder $contextBuilder;

    /**
     * Updates a list with available browser tabs (window handles) for the browser context
     *
     * @var TabActualizer
     */
    private TabActualizer $tabActualizer;

    /**
     * NewSessionOpener constructor.
     *
     * @param ContextBuilder $contextBuilder Builds a context object to provide a set of levers for the scraping session
     * @param TabActualizer  $tabActualizer  Updates a list with available browser tabs (window handles)
     */
    public function __construct(ContextBuilder $contextBuilder, TabActualizer $tabActualizer)
    {
        $this->contextBuilder = $contextBuilder;
        $this->tabActualizer  = $tabActualizer;
    }

    /**
     * {@inheritDoc}
     */
    public function openBrowser(): PromiseInterface
    {
        $browserContext = $this->contextBuilder->getContext();

        $webDriver = $browserContext->getWebDriver();

        $contextFilledPromise = $webDriver
            // sending a session create command to the Grid.
            ->createSession()
            // saving a session identifier for the browser context.
            ->then(fn (string $sessionIdentifier) => $this->onSessionIdentifier($browserContext, $sessionIdentifier))
            // setting up tab handles.
            ->then(fn () => $this->tabActualizer->actualizeTabs($browserContext))
            // forwarding a context instance as a resulting value.
            ->then(fn () => $browserContext)
        ;

        $contextFilledPromise = $contextFilledPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to open a remote browser (new session).', 0, $rejectionReason);
            }
        );

        return $contextFilledPromise;
    }

    /**
     * Assigns a session identifier for the browser context and forwards it to the next async handler
     *
     * @param BrowserContext $browserContext    Holds browser state and a driver reference to perform actions
     * @param string         $sessionIdentifier Identifier for the remote browser session
     *
     * @return void
     */
    private function onSessionIdentifier(BrowserContext $browserContext, string $sessionIdentifier): void
    {
        $browserContext->setHubSession($sessionIdentifier);
    }
}
