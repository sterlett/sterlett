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

namespace Sterlett\Tests\HardPrice\Item;

use PHPUnit\Framework\TestCase;
use Sterlett\Dto\Hardware\Item;
use Sterlett\HardPrice\Item\Parser as ItemParser;
use Traversable;

/**
 * Tests hardware items parsing for HardPrice website
 */
final class ParserTest extends TestCase
{
    /**
     * Extracts a list with hardware items from the website page content
     *
     * @var ItemParser
     */
    private ItemParser $itemParser;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->itemParser = new ItemParser();
    }

    /**
     * Ensures hardware items are properly parsed from the expected response contents
     *
     * @return void
     */
    public function testHardwareItemsAreParsed(): void
    {
        $responseBodyContents = file_get_contents(__DIR__ . '/../../../_data/cpus.json.raw');

        $itemListActual = $this->itemParser->parse($responseBodyContents);

        $itemArrayActual = [...$itemListActual];

        /** @var Item[] $itemArrayActual */

        foreach ($itemArrayActual as $hardwareItem) {
            $this->assertInstanceOf(
                Item::class,
                $hardwareItem,
                'Parser should return an iterable collection of ' . Item::class
            );
        }

        $dataExpected = [
            ['Процессор AMD RYZEN 5 3600 OEM AM4 Matisse', 2253],
            ['Процессор Intel Core i5 10400F OEM Comet Lake LGA1200 (CM8070104290716)', 2767],
        ];

        $this->assertEquals($dataExpected[0][0], $itemArrayActual[0]->getName(), 'Incorrect parsing.');
        $this->assertEquals($dataExpected[0][1], $itemArrayActual[0]->getIdentifier(), 'Incorrect parsing.');

        $this->assertEquals($dataExpected[1][0], $itemArrayActual[1]->getName(), 'Incorrect parsing.');
        $this->assertEquals($dataExpected[1][1], $itemArrayActual[1]->getIdentifier(), 'Incorrect parsing.');
    }
}
