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

namespace Sterlett\Hardware\Price\Provider\HardPrice;

use LogicException;

class Authentication
{
    private ?iterable $cookies;

    private ?string $csrfToken;

    public function __construct()
    {
        $this->cookies   = [];
        $this->csrfToken = null;
    }

    public function getCookies(): iterable
    {
        return $this->cookies;
    }

    public function addCookie(string $cookie): void
    {
        $this->cookies[] = $cookie;
    }

    public function getCsrfToken(): string
    {
        if (!is_string($this->csrfToken)) {
            throw new LogicException('CSRF token for the authentication DTO must be set explicitly.');
        }

        return $this->csrfToken;
    }

    public function setCsrfToken(string $csrfToken): void
    {
        $this->csrfToken = $csrfToken;
    }
}
