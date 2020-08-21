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
use Sterlett\Request\Uri\Normalizer\PathPrefixNormalizer;

/**
 * A wrapper for URI matcher that uses configured normalizer to prepare a given URI path for match test
 */
class PathNormalizingMatcher implements MatcherInterface
{
    /**
     * Wrapped service with base URI matching logic
     *
     * @var MatcherInterface
     */
    private MatcherInterface $uriMatcher;

    /**
     * Prepares URI path for match test
     *
     * Note: inject an interface instead, if more normalizers will be required (e.g. chained normalizer)
     *
     * @var PathPrefixNormalizer
     */
    private PathPrefixNormalizer $uriPathNormalizer;

    /**
     * PathNormalizingMatcher constructor.
     *
     * @param MatcherInterface     $uriMatcher        Wrapped service with base URI matching logic
     * @param PathPrefixNormalizer $uriPathNormalizer Prepares URI path for match test
     */
    public function __construct(MatcherInterface $uriMatcher, PathPrefixNormalizer $uriPathNormalizer)
    {
        $this->uriMatcher        = $uriMatcher;
        $this->uriPathNormalizer = $uriPathNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function match(string $uriPath): ?Match
    {
        $uriPathNormalized = $this->uriPathNormalizer->normalize($uriPath);

        return $this->uriMatcher->match($uriPathNormalized);
    }
}
