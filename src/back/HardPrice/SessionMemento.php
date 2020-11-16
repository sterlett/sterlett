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

namespace Sterlett\HardPrice;

/**
 * Holds a shared context with authentication data (cookies, tokens, etc.) to maintain a single browsing session during
 * price parsing
 */
final class SessionMemento
{
    /**
     * Represents a shared browsing session
     *
     * @var Authentication|null
     */
    private ?Authentication $session;

    /**
     * SessionMemento constructor.
     */
    public function __construct()
    {
        $this->session = null;
    }

    /**
     * Returns a session data or a NULL, if there is no browsing session in active state
     *
     * @return Authentication|null
     */
    public function getSession(): ?Authentication
    {
        return $this->session;
    }

    /**
     * Sets a data for the browsing session
     *
     * @param Authentication $session Session data
     *
     * @return void
     */
    public function setSession(Authentication $session): void
    {
        $this->session = $session;
    }
}
