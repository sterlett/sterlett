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

namespace Sterlett\HardPrice\Parser;

use Ds\Set;
use Sterlett\Hardware\PriceInterface;
use Traversable;

/**
 * Transforms price data from the raw format to the list of application-level DTOs
 */
class PriceParser
{
    /**
     * Price record pattern to extract an external store (seller) identifier and price amount
     *
     * @var string
     */
    private const RECORD_PATTERN = '/data-store.*(\d+?)(?=\\\\|"|\').*>(?=\d|\s)([\d\s.,]+)[^\d\s]*</Ui';

    /**
     * Returns a list with hardware price DTOs
     *
     * @param string $data Price data in raw format
     *
     * @return Traversable<PriceInterface>|PriceInterface[]
     */
    public function parse(string $data): iterable
    {
        // a rough offset, representing cursor position to extract data from the next price record.
        $offsetEstimated = 0;
        // we will capture only the first (considered as current) price from each seller.
        $idOccurrenceSet = new Set();

        $matches = [];

        while (1 === preg_match(self::RECORD_PATTERN, $data, $matches, PREG_OFFSET_CAPTURE, $offsetEstimated)) {
            $offsetEstimated = $matches[2][1];

            $sellerExternalId = (int) $matches[1][0];

            $priceAmount           = $matches[2][0];
            $priceAmountNormalized = (int) str_replace([' ', '.', ','], '', $priceAmount);

            if ($idOccurrenceSet->contains($sellerExternalId)) {
                continue 1;
            }

            yield [$sellerExternalId, $priceAmountNormalized];

            $idOccurrenceSet->add($sellerExternalId);
        }
    }
}
