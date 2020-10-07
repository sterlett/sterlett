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

namespace Sterlett\Hardware\Price\Provider\HardPrice;

class IdParser
{
    private const RECORD_PATTERN = '/prdname">([^<]+)<.*count">([^<]+)</';

    public function parse(string $data): iterable
    {
        $dataArray = json_decode($data);

        foreach ($dataArray as $dataObject) {
            yield $dataObject->id;
        }
    }
}
