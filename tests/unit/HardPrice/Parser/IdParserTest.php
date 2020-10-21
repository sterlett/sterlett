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

namespace Sterlett\Tests\HardPrice\Parser;

use PHPUnit\Framework\TestCase;
use Sterlett\HardPrice\Parser\IdParser;
use Traversable;

/**
 * Tests hardware identifiers parsing for HardPrice website
 */
final class IdParserTest extends TestCase
{
    /**
     * Extracts a list with hardware identifiers from the website page content
     *
     * @var IdParser
     */
    private IdParser $idParser;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->idParser = new IdParser();
    }

    /**
     * Ensures hardware identifiers are properly parsed from the expected response contents
     *
     * @return void
     */
    public function testHardwareIdentifiersAreParsed(): void
    {
        $responseBodyContents = file_get_contents(__DIR__ . '/../../../_data/cpus.json.raw');

        $idArrayExpected = [2253, 2767];

        $idListActual = $this->idParser->parse($responseBodyContents);

        if ($idListActual instanceof Traversable) {
            $idArrayActual = iterator_to_array($idListActual);
        } else {
            $idArrayActual = (array) $idListActual;
        }

        $this->assertEqualsCanonicalizing(
            $idArrayExpected,
            $idArrayActual,
            'Hardware identifiers are not properly parsed.'
        );
    }
}
