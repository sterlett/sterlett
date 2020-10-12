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

use RuntimeException;
use Traversable;

/**
 * Transforms external identifiers from the raw format to the iterable list of normalized values
 */
class IdParser
{
    /**
     * Returns an iterable collection of external identifiers
     *
     * @param string $data A list of hardware items with properties in a raw format
     *
     * @return Traversable<int>|int[]
     */
    public function parse(string $data): iterable
    {
        // todo: migrate from sync json_decode to async regexp approach

        $dataArray = json_decode($data, true);

        $jsonDecodeErrorCode = json_last_error();

        if (0 !== $jsonDecodeErrorCode) {
            $jsonDecodeErrorMessage = json_last_error_msg();

            $deserializationExceptionMessage = sprintf(
                'Unable to parse hardware identifiers: %s. (%s)',
                $jsonDecodeErrorMessage,
                $data
            );

            throw new RuntimeException($deserializationExceptionMessage);
        }

        foreach ($dataArray as $dataRecord) {
            $externalIdNormalized = isset($dataRecord['id']) ? (int) $dataRecord['id'] : null;

            if (null === $externalIdNormalized) {
                throw new RuntimeException('Unable to parse a hardware identifier. Unexpected data format.');
            }

            yield $externalIdNormalized;
        }
    }
}
