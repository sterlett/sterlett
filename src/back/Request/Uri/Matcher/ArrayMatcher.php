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

namespace Sterlett\Request\Uri\Matcher;

use Sterlett\Request\Uri\Match;
use Sterlett\Request\Uri\MatcherInterface;

/**
 * Finds context to decide how response will be generated using array of mappings (uri-actionName).
 * Order for condition checks is preserved, partial URI match is not enough for positive result.
 */
class ArrayMatcher implements MatcherInterface
{
    /**
     * URI to action mappings
     *
     * @var iterable
     */
    private iterable $uriToAction;

    /**
     * ArrayMatcher constructor.
     *
     * @param iterable $uriToAction URI to action mappings
     */
    public function __construct(iterable $uriToAction)
    {
        $this->uriToAction = $uriToAction;
    }

    /**
     * {@inheritDoc}
     */
    public function match(string $uriPath): ?Match
    {
        $uriMatch = null;

        foreach ($this->uriToAction as $uriForMatch => $actionName) {
            if (!$this->isUriMatch($uriPath, $uriForMatch)) {
                continue;
            }

            $uriMatch = new Match();

            $actionNameNormalized = (string) $actionName;
            $uriMatch->setActionName($actionNameNormalized);

            break;
        }

        return $uriMatch;
    }

    /**
     * Returns positive whenever a given URI path matches the configured one
     *
     * @param string $uriPath     The given URI path
     * @param string $uriForMatch URI from the configured mappings
     *
     * @return bool
     */
    private function isUriMatch(string $uriPath, string $uriForMatch): bool
    {
        $uriPathNormalized     = mb_strtolower(trim($uriPath));
        $uriForMatchNormalized = mb_strtolower(trim($uriForMatch));

        $isUriMatch = $uriPathNormalized === $uriForMatchNormalized;

        return $isUriMatch;
    }
}
