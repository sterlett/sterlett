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

namespace Sterlett\HardPrice\Price\Parser;

use Ds\Set;
use Traversable;

/**
 * Recognizes data primitives to make properties for the price DTOs from the response message body
 */
class Tokenizer
{
    /**
     * Price record pattern to recognize an external store (seller) identifier and price amount
     *
     * Requirements:
     * - no multiline
     *
     * @var string
     */
    private const RECORD_PATTERN = '/data-store=\\\\?"(\d+?)(?=\\\\|"|\').*>(?=\d|\s)([\d\s.,]+)[^\d\s]*<\//Ui';

    /**
     * Returns a list with data primitives to build properties for a price DTO
     *
     * @param string $data Price data in raw format
     *
     * @return Traversable<array>|array[]
     */
    public function tokenize(string $data): iterable
    {
        // a rough offset, representing cursor position to extract data from the next price record.
        $offsetEstimated = 0;
        // we will capture only the first (considered as current) price from each seller.
        $idOccurrenceSet = new Set();

        $matches = [];

        while (1 === preg_match(self::RECORD_PATTERN, $data, $matches, PREG_OFFSET_CAPTURE, $offsetEstimated)) {
            $offsetEstimated = $matches[2][1];

            $storeExternalId = (int) $matches[1][0];

            $priceAmount           = $matches[2][0];
            $priceAmountNormalized = (int) str_replace([' ', '.', ','], '', $priceAmount);

            if ($idOccurrenceSet->contains($storeExternalId)) {
                continue 1;
            }

            yield [$storeExternalId, $priceAmountNormalized];

            $idOccurrenceSet->add($storeExternalId);
        }
    }
}
