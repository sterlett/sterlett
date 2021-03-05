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

namespace Sterlett\Browser\Session;

use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Browser\Context as BrowserContext;
use Throwable;
use Traversable;

/**
 * Selects an existing WebDriver session from the set of available ones
 */
class Resumer
{
    /**
     * Returns a promise that resolves to a string, representing a WebDriver session identifier, which will be reused
     * from the active pool
     *
     * @param BrowserContext $context Holds browser state and a driver reference to perform actions
     *
     * @return PromiseInterface<string>
     */
    public function resumeSession(BrowserContext $context): PromiseInterface
    {
        $webDriver = $context->getWebDriver();

        $sessionIdentifierPromise = $webDriver
            // requesting available session identifiers.
            ->getSessionIdentifiers()
            // selecting one to use.
            ->then(fn (iterable $sessionIdentifiers) => $this->pickOne($sessionIdentifiers))
        ;

        $sessionIdentifierPromise = $sessionIdentifierPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException(
                    'Unable to resume an existing browser session (session resumer).',
                    0,
                    $rejectionReason
                );
            }
        );

        return $sessionIdentifierPromise;
    }

    /**
     * Returns a session identifier to reuse by the current scraping session
     *
     * @param Traversable<string>|string[] $sessionIdentifiers Available WebDriver sessions
     *
     * @return string
     *
     * @throws RuntimeException If there are no sessions available for reuse
     */
    private function pickOne(iterable $sessionIdentifiers): string
    {
        foreach ($sessionIdentifiers as $sessionIdentifier) {
            return $sessionIdentifier;
        }

        throw new RuntimeException('Unable to select a session identifier from the available ones.');
    }
}
