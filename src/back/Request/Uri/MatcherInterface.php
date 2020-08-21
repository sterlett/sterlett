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

namespace Sterlett\Request\Uri;

/**
 * Finds context to decide which action should be used to generate a response for the given request.
 *
 * Use a simple ArrayMatcher or consider to implement a bridge for the "symfony/routing" component set if more
 * complex conditions are needed.
 *
 * @see https://symfony.com/doc/current/routing.html
 */
interface MatcherInterface
{
    /**
     * Returns a context instance of the positive match or NULL if the given URI path can't be processed
     *
     * @param string $uriPath URI path from the given request (e.g. /path/to/resource.json)
     *
     * @return Match|null
     */
    public function match(string $uriPath): ?Match;
}
