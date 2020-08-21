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

namespace Sterlett\Request\Uri\Normalizer;

/**
 * Removes a prefix from the given URI path to make it canonical and relevant to the original list of routes (e.g. to
 * strip any markers assigned by a reverse proxy/gateway for its routing logic and load balancing).
 *
 * Note: you may also consider to use native URI path management (if any) provided by your gateway first.
 */
class PathPrefixNormalizer
{
    /**
     * URI path prefix to strip
     *
     * @var string
     */
    private string $uriPathPrefix;

    /**
     * PathPrefixNormalizer constructor.
     *
     * @param string $uriPathPrefix URI path prefix to strip
     */
    public function __construct(string $uriPathPrefix)
    {
        $this->uriPathPrefix = $uriPathPrefix;
    }

    /**
     * Returns URI path that was normalized using the specified rules
     *
     * @param string $uriPath URI path for normalization
     *
     * @return string
     */
    public function normalize(string $uriPath): string
    {
        // todo: extract to the separate normalizer
        $uriPathNormalized = mb_strtolower(trim($uriPath));

        if (in_array($this->uriPathPrefix, ['', '/'])) {
            return $uriPathNormalized;
        }

        $prefixOffset = mb_strpos($uriPathNormalized, $this->uriPathPrefix);

        if (0 !== $prefixOffset) {
            return $uriPathNormalized;
        }

        $prefixLength      = mb_strlen($this->uriPathPrefix);
        $uriPathNormalized = substr($uriPathNormalized, $prefixLength);

        return $uriPathNormalized;
    }
}
