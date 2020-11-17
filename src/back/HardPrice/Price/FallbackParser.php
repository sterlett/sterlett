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

namespace Sterlett\HardPrice\Price;

use Iterator;
use RuntimeException;
use Sterlett\Dto\Hardware\Price;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Yields application-level DTOs while processing raw data from the fallback endpoint (single response)
 */
class FallbackParser
{
    /**
     * Writes and reads values to/from an object/array graph
     *
     * @var PropertyAccessorInterface
     */
    private PropertyAccessorInterface $propertyAccessor;

    /**
     * FallbackParser constructor.
     *
     * @param PropertyAccessorInterface $propertyAccessor Writes and reads values to/from an object/array graph
     */
    public function __construct(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Returns a list (iterator) with hardware price DTOs
     *
     * @param string $data Price data for all available hardware items at once, raw format
     *
     * @return Iterator<Price>
     */
    public function parse(string $data): Iterator
    {
        // todo: regex approach to eliminate excess blocking time

        $dataArray = json_decode($data);

        $jsonDecodeErrorCode = json_last_error();

        if (0 !== $jsonDecodeErrorCode) {
            $jsonDecodeErrorMessage = json_last_error_msg();

            $deserializationExceptionMessage = sprintf(
                'Unable to parse hardware prices (fallback): %s. (%s)',
                $jsonDecodeErrorMessage,
                $data
            );

            throw new RuntimeException($deserializationExceptionMessage);
        }

        foreach ($dataArray as $dataRecord) {
            $itemActive = (int) $this->propertyAccessor->getValue($dataRecord, 'active');

            if (1 !== $itemActive) {
                continue;
            }

            $itemIdentifier = (int) $this->propertyAccessor->getValue($dataRecord, 'id');
            $itemName       = (string) $this->propertyAccessor->getValue($dataRecord, 'title');

            $itemPrices = [];

            // todo: inject store mapper/storage to get more store identifiers
            $storeIdentifiers = [
                'fcenter',
                'oldi',
                'dns',
                'regard',
                'citilink',
                'eldorado',
                'mvideo',
                'pleer',
                'svyaznoy',
                'notik',
                'computeruniverse',
            ];

            foreach ($storeIdentifiers as $storeIdentifier) {
                $fieldPriceName = $storeIdentifier . '_price';

                if (!$this->propertyAccessor->isReadable($dataRecord, $fieldPriceName)) {
                    continue;
                }

                $itemPriceAmount = (int) $this->propertyAccessor->getValue($dataRecord, $fieldPriceName);

                if ($itemPriceAmount < 1) {
                    continue;
                }

                $itemPrice = new Price();
                $itemPrice->setHardwareName($itemName);
                $itemPrice->setSellerIdentifier($storeIdentifier);
                $itemPrice->setAmount($itemPriceAmount);
                $itemPrice->setPrecision(0);
                $itemPrice->setCurrency('RUB');

                $itemPrices[] = $itemPrice;
            }

            if (empty($itemPrices)) {
                continue;
            }

            yield $itemIdentifier => $itemPrices;
        }
    }
}
