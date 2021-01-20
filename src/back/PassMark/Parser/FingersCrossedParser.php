<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020-2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\PassMark\Parser;

use Sterlett\Dto\Hardware\Benchmark;
use Sterlett\Hardware\Benchmark\ParserInterface;

/**
 * Transforms raw benchmark data from the PassMark website to the list of application-level DTOs according to the
 * defined benchmark interface; uses regular expression with offset capturing.
 *
 * User-side should keep their fingers crossed in order to run this benchmark parser for the expected results.
 *
 * Usage example:
 *
 *    ."".
 *    \   \  ."",
 *     \   \/  /
 *      \  /  /
 *       \/  /
 *       /  / \,-._
 *      {  ` _/  / ;
 *      |  /` ) /  /
 *      | /  /_/\_/\
 *      |/  /      |
 *      (  ' \ '-  |
 *       \    `.  /
 *        |      |
 *        |      |
 */
class FingersCrossedParser implements ParserInterface
{
    /**
     * RegExp pattern to extract benchmark records from the given source
     *
     * @var string
     */
    private const RECORD_PATTERN = '/prdname">([^<]+)<.*count">([^<]+)</';

    /**
     * {@inheritDoc}
     */
    public function parse(string $data): iterable
    {
        // a rough offset, representing cursor position to extract data from the next benchmark record.
        $offsetEstimated = 0;

        $matches = [];

        while (1 === preg_match(self::RECORD_PATTERN, $data, $matches, PREG_OFFSET_CAPTURE, $offsetEstimated)) {
            // todo: handle exceptions, with context logging

            $benchmarkHardwareName = $matches[1][0];
            $benchmarkValue        = $matches[2][0];

            $benchmarkValueNormalized = preg_replace('/[.,]/', '', $benchmarkValue);

            $benchmark = new Benchmark();
            $benchmark->setHardwareName($benchmarkHardwareName);
            $benchmark->setValue($benchmarkValueNormalized);

            yield $benchmark;

            $offsetEstimated = $matches[2][1];
        }
    }
}
