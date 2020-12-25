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

use LogicException;

/**
 * Holds authentication data payload to mimic ajax request that has been sent from the browser
 */
class Authentication
{
    /**
     * An array of session cookies (i.e. "key=value", without any additional attributes like "Path" or "Expires")
     *
     * @var string[]
     */
    private array $cookies;

    /**
     * CSRF token
     *
     * @var string|null
     */
    private ?string $csrfToken;

    /**
     * Authentication constructor.
     */
    public function __construct()
    {
        $this->cookies   = [];
        $this->csrfToken = null;
    }

    /**
     * Returns a list of cookies for the website interaction session.
     *
     * Any persistent cookies (with specific attributes) must be converted into the session-compatible format.
     *
     * @return string[]
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Adds a new cookie to the authentication context
     *
     * @param string $cookie Session cookie (key=value), as a string
     *
     * @return void
     */
    public function addCookie(string $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    /**
     * Returns a CSRF token
     *
     * @return string|null
     */
    public function getCsrfToken(): ?string
    {
        return $this->csrfToken;
    }

    /**
     * Sets a CSRF token
     *
     * @param string $csrfToken CSRF token
     *
     * @return void
     */
    public function setCsrfToken(string $csrfToken): void
    {
        $this->csrfToken = $csrfToken;
    }
}
