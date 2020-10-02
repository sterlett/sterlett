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

namespace Sterlett\Tests\Hardware\Benchmark\Parser\PassMark;

use PHPUnit\Framework\TestCase;
use Sterlett\Hardware\Benchmark\Parser\PassMark\FingersCrossedParser;
use Sterlett\Hardware\BenchmarkInterface;
use Traversable;

/**
 * Tests PassMark "fingers crossed" parser for correct benchmark data parsing
 */
final class FingersCrossedParserTest extends TestCase
{
    /**
     * Transforms raw benchmark data from the PassMark website to the list of application-level DTOs
     *
     * @var FingersCrossedParser
     */
    private FingersCrossedParser $parser;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->parser = new FingersCrossedParser();
    }

    /**
     * Ensures that benchmark data is parsed using data in expected format
     *
     * @return void
     */
    public function testBenchmarksParsedUsingExpectedDataFormat(): void
    {
        $data = <<<HTML_CHUNK
            <span id="tip" class="tooltip">Tooltip Description Place Holder</span>
            <div class="main-tabs">
                <p onClick="flip( 'mark', 'value', 'tub-1', 'tub-2' );" id="tub-1" class="active">CPU Mark</p>
                <p onClick="flip( 'value', 'mark', 'tub-2', 'tub-1' );" id="tub-2">Price Performance</p>
            </div>						
            <div class="charts">
                <div id="mark">							
                    <div class="chart">
                        <div class="chart_header">
                        <div class="chart_title">PassMark - CPU Mark</div>
                        <div class="chart_subtitle">High End CPUs</div>
                        <div class="chart_subtitle" style="font-size: small;">Updated 2nd of October 2020</div>
                    </div>
                    
                    <div class="chart_subheader">
                        <div class="chart_tabletitle1">CPU</div>    <div class="chart_tabletitle2">CPU Mark</div>    <div class="chart_tabletitle3">Price (USD)</div>  </div>
                        <div class="chart_body">
                        <ul class="chartlist">
                            <li id="rk3837"><span class="more_details" onclick="x(event, 1, 7, 64, 2, 'NA');"><a class="name" href="cpu.php?cpu=AMD+Ryzen+Threadripper+PRO+3995WX&amp;id=3837"></a></span><a href="cpu.php?cpu=AMD+Ryzen+Threadripper+PRO+3995WX&amp;id=3837"><span class="prdname">AMD Ryzen Threadripper PRO 3995WX</span><div><span class="index pink" style="width: 86%">(86%)</span></div><span class="count">88,673</span><span class="price-neww">NA</span></a></li>
                            <li id="rk3674"><span class="more_details" onclick="x(event, 2, 60, 64, 2, '$3,989.99');"><a class="name" href="cpu.php?cpu=AMD+Ryzen+Threadripper+3990X&amp;id=3674"></a></span><a href="cpu.php?cpu=AMD+Ryzen+Threadripper+3990X&amp;id=3674"><span class="prdname">AMD Ryzen Threadripper 3990X</span><div><span class="index yellow" style="width: 78%">(78%)</span></div><span class="count">79,930</span><span class="price-neww">$3,989.99</span></a></li>
                            <li id="rk3719"><span class="more_details" onclick="x(event, 3, 3, 64, 2, '$4,085.00');"><a class="name" href="cpu.php?cpu=AMD+EPYC+7702&amp;id=3719"></a></span><a href="cpu.php?cpu=AMD+EPYC+7702&amp;id=3719"><span class="prdname">AMD EPYC 7702</span><div><span class="index green" style="width: 69%">(69%)</span></div><span class="count">71,362</span><span class="price-neww">$4,085.00</span></a></li>
                            ...
HTML_CHUNK;

        $benchmarkTraversableOrArray = $this->parser->parse($data);

        if ($benchmarkTraversableOrArray instanceof Traversable) {
            $benchmarks = iterator_to_array($benchmarkTraversableOrArray);
        } else {
            $benchmarks = (array) $benchmarkTraversableOrArray;
        }

        /** @var BenchmarkInterface[] $benchmark */

        foreach ($benchmarks as $benchmark) {
            $this->assertInstanceOf(
                BenchmarkInterface::class,
                $benchmark,
                'Parser should return an iterable collection of ' . BenchmarkInterface::class
            );
        }

        $dataExpected = [
            ['AMD Ryzen Threadripper PRO 3995WX', '88,673'],
            ['AMD Ryzen Threadripper 3990X', '79,930'],
            ['AMD EPYC 7702', '71,362'],
        ];

        $this->assertEquals($dataExpected[0][0], $benchmarks[0]->getHardwareName(), 'Incorrect parsing.');
        $this->assertEquals($dataExpected[0][1], $benchmarks[0]->getValue(), 'Incorrect parsing.');

        $this->assertEquals($dataExpected[1][0], $benchmarks[1]->getHardwareName(), 'Incorrect parsing.');
        $this->assertEquals($dataExpected[1][1], $benchmarks[1]->getValue(), 'Incorrect parsing.');

        $this->assertEquals($dataExpected[2][0], $benchmarks[2]->getHardwareName(), 'Incorrect parsing.');
        $this->assertEquals($dataExpected[2][1], $benchmarks[2]->getValue(), 'Incorrect parsing.');
    }
}
