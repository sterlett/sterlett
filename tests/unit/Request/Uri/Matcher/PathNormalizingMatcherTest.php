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

namespace Sterlett\Tests\Request\Uri\Matcher;

use PHPUnit\Framework\TestCase;
use Sterlett\Request\Uri\Normalizer\PathPrefixNormalizer;
use Sterlett\Request\Uri\Match;
use Sterlett\Request\Uri\Matcher\ArrayMatcher;
use Sterlett\Request\Uri\Matcher\PathNormalizingMatcher;

/**
 * Tests if PathNormalizingMatcher compares URI against a predefined route correctly
 */
final class PathNormalizingMatcherTest extends TestCase
{
    /**
     * Tests match method for positive uppercase URI match case with configured prefix
     *
     * @return void
     */
    public function testPositiveMatchOnUppercaseUriWithPrefix(): void
    {
        $arrayMatcher = new ArrayMatcher(['/url 3' => 'Resource 3, w/ whitespace character.']);
        $normalizer   = new PathPrefixNormalizer('/some-prefix');

        $normalizingMatcher = new PathNormalizingMatcher($arrayMatcher, $normalizer);

        $uriMatch = $normalizingMatcher->match('/some-prefix/UrL 3');

        $this->assertInstanceOf(
            Match::class,
            $uriMatch,
            'PathNormalizingMatcher should return an instance of Match class if URI is found.'
        );

        $uriMatchActionName = $uriMatch->getActionName();

        $this->assertEquals(
            'Resource 3, w/ whitespace character.',
            $uriMatchActionName,
            'Uri match context should contain an appropriate action name if URI is found.'
        );
    }
}
