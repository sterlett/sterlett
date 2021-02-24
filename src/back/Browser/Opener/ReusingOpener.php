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
use Sterlett\Browser\OpenerInterface;
use Throwable;

/**
 * Reusing browser opener will try to use an existing browser session and, if failed, will open a new one
 */
class ReusingOpener implements OpenerInterface
{
    /**
     * Implementation to "open" a browser with existing session
     *
     * @var OpenerInterface
     */
    private OpenerInterface $existingSessionOpener;

    /**
     * Implementation to create a new browsing session
     *
     * @var OpenerInterface
     */
    private OpenerInterface $newSessionOpener;

    /**
     * ReusingOpener constructor.
     *
     * @param OpenerInterface $existingSessionOpener Implementation to "open" a browser with existing session
     * @param OpenerInterface $newSessionOpener      Implementation to create a new browsing session
     */
    public function __construct(OpenerInterface $existingSessionOpener, OpenerInterface $newSessionOpener)
    {
        $this->existingSessionOpener = $existingSessionOpener;
        $this->newSessionOpener      = $newSessionOpener;
    }

    /**
     * {@inheritDoc}
     */
    public function openBrowser(): PromiseInterface
    {
        $browserReadyPromise = $this->existingSessionOpener
            // trying to get an existing session first.
            ->openBrowser()
            // initializing a new session as a fallback.
            ->then(
                null,
                fn (Throwable $rejectionReason) => $this->newSessionOpener->openBrowser()
            )
        ;

        return $browserReadyPromise;
    }
}
