<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Hardware\Price\Repository;

use React\MySQL\QueryResult;
use Sterlett\Dto\Hardware\Price;
use Sterlett\Repository\HydratorInterface;

/**
 * Transforms a raw dataset to a collection of price DTOs
 */
class Hydrator implements HydratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function hydrate(QueryResult $queryResult): iterable
    {
        foreach ($queryResult->resultRows as $resultRow) {
            $hardwarePrice = new Price();
            $hardwarePrice->setHardwareName($resultRow['hardware_name']);
            $hardwarePrice->setHardwareImage($resultRow['hardware_image_uri']);
            $hardwarePrice->setSellerIdentifier($resultRow['seller_name']);
            $hardwarePrice->setAmount((int) $resultRow['price_amount']);
            // todo: extract a real precision from the row value
            $hardwarePrice->setPrecision(0);
            $hardwarePrice->setCurrency($resultRow['currency_label']);

            yield $hardwarePrice;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames(): array
    {
        return [
            'hp.`hardware_name`',
            'hp.`hardware_image_uri`',
            'hp.`seller_name`',
            'hp.`price_amount`',
            'hp.`currency_label`',
        ];
    }
}
