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

namespace Sterlett\Browser\Opener;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context\Builder as ContextBuilder;
use Sterlett\Browser\OpenerInterface;
use Sterlett\Browser\Session\Resumer as SessionResumer;
use Sterlett\Browser\Tab\Actualizer as TabActualizer;
use Throwable;

/**
 * Opens a remote browser and picks an existing browsing session for reuse
 */
class ExistingSessionOpener implements OpenerInterface
{
    /**
     * Builds a context object to provide a set of levers (WebDriver reference, loop bridge) for the scraping session
     *
     * @var ContextBuilder
     */
    private ContextBuilder $contextBuilder;

    /**
     * Selects an existing WebDriver session from the available set
     *
     * @var SessionResumer
     */
    private SessionResumer $sessionResumer;

    /**
     * Updates a list with available browser tabs (window handles)
     *
     * @var TabActualizer
     */
    private TabActualizer $tabActualizer;

    /**
     * ExistingSessionOpener constructor.
     *
     * @param ContextBuilder $contextBuilder Builds a context object to provide a set of levers for the scraping session
     * @param SessionResumer $sessionResumer Selects an existing WebDriver session from the available set
     * @param TabActualizer  $tabActualizer  Updates a list with available browser tabs (window handles)
     */
    public function __construct(
        ContextBuilder $contextBuilder,
        SessionResumer $sessionResumer,
        TabActualizer $tabActualizer
    ) {
        $this->contextBuilder = $contextBuilder;
        $this->sessionResumer = $sessionResumer;
        $this->tabActualizer  = $tabActualizer;
    }

    /**
     * {@inheritDoc}
     */
    public function openBrowser(): PromiseInterface
    {
        $browserContext = $this->contextBuilder->getContext();

        $contextFilledPromise = $this->sessionResumer
            // executing session reuse logic.
            ->resumeSession($browserContext)
            // saving a session identifier for the browser context.
            ->then(fn (string $sessionIdentifier) => $browserContext->setHubSession($sessionIdentifier))
            // setting up tab identifiers.
            ->then(fn () => $this->tabActualizer->actualizeTabs($browserContext))
            // forwarding a context instance as a resval.
            ->then(fn () => $browserContext)
        ;

        $contextFilledPromise = $contextFilledPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to open a remote browser (existing session).', 0, $rejectionReason);
            }
        );

        return $contextFilledPromise;
    }
}
