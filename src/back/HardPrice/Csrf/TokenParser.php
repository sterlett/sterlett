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

namespace Sterlett\HardPrice\Csrf;

use RuntimeException;

/**
 * Extracts a CSRF token from the website page content
 */
class TokenParser
{
    /**
     * RegExp pattern for CSRF token extracting
     *
     * todo: make it safer
     *
     * @var string
     */
    private const TOKEN_PATTERN = "/token: '([0-9a-z]+)',/";

    /**
     * Returns a CSRF token, extracted from the given page content
     *
     * @param string $data Page content as a string
     *
     * @return string
     */
    public function parse(string $data): string
    {
        $matches = [];

        $matchResult = preg_match(self::TOKEN_PATTERN, $data, $matches);

        if (1 !== $matchResult) {
            // todo: data snapshot logging

            throw new RuntimeException('Unable to parse CSRF token: unexpected format.');
        }

        $csrfToken = $matches[1];

        return $csrfToken;
    }
}
