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

namespace Sterlett\Hardware;

/**
 * Represents a price record for the specific hardware in the store
 */
interface PriceInterface
{
    /**
     * Returns hardware name
     *
     * @return string
     */
    public function getHardwareName(): string;

    /**
     * Returns hardware image
     *
     * @return string
     */
    public function getHardwareImage(): string;

    /**
     * Returns seller's identifier
     *
     * @return string
     */
    public function getSellerIdentifier(): string;

    /**
     * Returns price amount with preserved precision data, but stripped ".", "," and other context-related symbols.
     *
     * For example, price amount of 155,8312 USD should be stored as 1558312 integer; precision can still be obtained
     * by the {@link getPrecision()} property, which should return 4 in this case.
     *
     * @return int
     */
    public function getAmount(): int;

    /**
     * Returns precision number for the price amount
     *
     * @return int
     */
    public function getPrecision(): int;

    /**
     * Returns a currency symbol for the price (e.g. 'USD' or 'RUB')
     *
     * @return string
     */
    public function getCurrency(): string;
}
