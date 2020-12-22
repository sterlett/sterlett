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

namespace Sterlett\Dto\Hardware;

use LogicException;
use Sterlett\Hardware\PriceInterface;

/**
 * Data context for a single price record from the price provider
 *
 * todo: for php 8.0+ context - named constructor arguments are preferred, instead of fluent setters
 *  https://wiki.php.net/rfc/named_params
 */
final class Price implements PriceInterface
{
    /**
     * Hardware name
     *
     * @var string|null
     */
    private ?string $hardwareName;

    /**
     * Seller (store) identifier, to which price record belongs to
     *
     * @var string|null
     */
    private ?string $sellerIdentifier;

    /**
     * Price amount
     *
     * @var int|null
     */
    private ?int $amount;

    /**
     * Price precision
     *
     * @var int|null
     */
    private ?int $precision;

    /**
     * Currency label
     *
     * @var string|null
     */
    private ?string $currency;

    /**
     * Price constructor.
     */
    public function __construct()
    {
        $this->hardwareName     = null;
        $this->sellerIdentifier = null;
        $this->amount           = null;
        $this->precision        = null;
        $this->currency         = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getHardwareName(): string
    {
        if (!is_string($this->hardwareName)) {
            throw new LogicException('Hardware name for the price DTO must be set explicitly.');
        }

        return $this->hardwareName;
    }

    /**
     * Sets hardware name
     *
     * @param string $hardwareName Hardware name
     *
     * @return void
     */
    public function setHardwareName(string $hardwareName): void
    {
        $this->hardwareName = $hardwareName;
    }

    /**
     * {@inheritDoc}
     */
    public function getSellerIdentifier(): string
    {
        if (!is_string($this->sellerIdentifier)) {
            throw new LogicException("Seller's identifier for the price DTO must be set explicitly.");
        }

        return $this->sellerIdentifier;
    }

    /**
     * Sets seller identifier
     *
     * @param string $sellerIdentifier Seller (store) identifier
     *
     * @return void
     */
    public function setSellerIdentifier(string $sellerIdentifier): void
    {
        $this->sellerIdentifier = $sellerIdentifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getAmount(): int
    {
        if (!is_int($this->amount)) {
            throw new LogicException('Amount for the price DTO must be set explicitly.');
        }

        return $this->amount;
    }

    /**
     * Sets price amount
     *
     * @param int $amount Price amount
     *
     * @return void
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrecision(): int
    {
        if (!is_int($this->precision)) {
            throw new LogicException('Precision for the price DTO must be set explicitly.');
        }

        return $this->precision;
    }

    /**
     * Sets price precision
     *
     * @param int $precision Price precision
     *
     * @return void
     */
    public function setPrecision(int $precision): void
    {
        $this->precision = $precision;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrency(): string
    {
        if (!is_string($this->currency)) {
            throw new LogicException('Currency for the price DTO must be set explicitly.');
        }

        return $this->currency;
    }

    /**
     * Sets currency label
     *
     * @param string $currency Currency label
     *
     * @return void
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }
}
