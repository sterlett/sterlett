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

use Sterlett\Request\Uri\Match;
use Sterlett\Request\Uri\Matcher\ArrayMatcher;
use PHPUnit\Framework\TestCase;

/**
 * Tests if ArrayMatcher compares URI against a predefined set correctly
 */
final class ArrayMatcherTest extends TestCase
{
    /**
     * Performs URI match using predefined array of mappings (uri-actionName)
     *
     * @var ArrayMatcher
     */
    private ArrayMatcher $uriMatcher;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->uriMatcher = new ArrayMatcher(
            [
                '/'        => 'Resource, accessible by the root path.',
                '/url1'    => 'Resource 1.',
                'url2'     => 'Resource 2, w/o trailing slash at start.',
                '/url 3'   => 'Resource 3, w/ whitespace character.',
                '/юникод4' => 'Resource 4, w/ unicode characters.',
            ]
        );
    }

    /**
     * Tests match method for negative match case
     *
     * @return void
     */
    public function testNegativeMatchOnUri(): void
    {
        $uriMatch = $this->uriMatcher->match('/undefined-uri-path');

        $this->assertNull(
            $uriMatch,
            'ArrayMatcher should return a NULL value if URI is not found.'
        );
    }

    /**
     * Tests match method for positive ASCII URI match case
     *
     * @return void
     */
    public function testPositiveMatchOnAsciiUri(): void
    {
        $uriMatch = $this->uriMatcher->match('/url1');

        $this->assertInstanceOf(
            Match::class,
            $uriMatch,
            'ArrayMatcher should return an instance of Match class if URI is found.'
        );

        $uriMatchActionName = $uriMatch->getActionName();

        $this->assertEquals(
            'Resource 1.',
            $uriMatchActionName,
            'Uri match context should contain an appropriate action name if URI is found.'
        );
    }

    /**
     * Tests match method for positive unicode URI match case
     *
     * @return void
     */
    public function testPositiveMatchOnUnicodeUri(): void
    {
        $uriMatch = $this->uriMatcher->match('/юникод4');

        $this->assertInstanceOf(
            Match::class,
            $uriMatch,
            'ArrayMatcher should return an instance of Match class if URI is found.'
        );

        $uriMatchActionName = $uriMatch->getActionName();

        $this->assertEquals(
            'Resource 4, w/ unicode characters.',
            $uriMatchActionName,
            'Uri match context should contain an appropriate action name if URI is found.'
        );
    }
}
