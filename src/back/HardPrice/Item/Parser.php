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

namespace Sterlett\HardPrice\Item;

use RuntimeException;
use Sterlett\Dto\Hardware\Item;
use Traversable;

/**
 * Transforms hardware items data from the raw format to the iterable list of normalized values (DTOs)
 */
class Parser
{
    /**
     * Returns an iterable collection of hardware item DTOs
     *
     * @param string $data A list of hardware item records in a raw format
     *
     * @return Traversable<Item>|Item[]
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
            // todo: extract domain-based data filtering into the separate service
            $isActive = isset($dataRecord['active']) ? (int) $dataRecord['active'] : 0;

            if (1 !== $isActive) {
                continue;
            }

            $externalIdNormalized = isset($dataRecord['id']) ? (int) $dataRecord['id'] : null;

            if (null === $externalIdNormalized) {
                throw new RuntimeException('Unable to parse a hardware identifier. Unexpected data format.');
            }

            $hardwareItem = new Item();
            $hardwareItem->setIdentifier($externalIdNormalized);

            $nameNormalized = isset($dataRecord['title']) ? (string) $dataRecord['title'] : null;

            if (null === $nameNormalized) {
                throw new RuntimeException('Unable to parse hardware name. Unexpected data format.');
            }

            $hardwareItem->setName($nameNormalized);

            $pageUriNormalized = isset($dataRecord['url']) ? (string) $dataRecord['url'] : null;

            if (null === $pageUriNormalized) {
                throw new RuntimeException('Unable to parse hardware page URI. Unexpected data format.');
            }

            $hardwareItem->setPageUri($pageUriNormalized);

            yield $hardwareItem;
        }
    }
}
