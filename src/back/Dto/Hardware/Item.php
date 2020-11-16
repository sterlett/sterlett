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

/**
 * Contains information for a single hardware item
 */
final class Item
{
    /**
     * Hardware item identifier
     *
     * @var int|null
     */
    private ?int $identifier;

    /**
     * Hardware item name
     *
     * @var string|null
     */
    private ?string $name;

    /**
     * URI with item information on the website
     *
     * @var string|null
     */
    private ?string $pageUri;

    /**
     * Item constructor.
     */
    public function __construct()
    {
        $this->identifier = null;
        $this->name       = null;
        $this->pageUri    = null;
    }

    /**
     * Returns hardware item identifier
     *
     * @return int
     */
    public function getIdentifier(): int
    {
        if (!is_int($this->identifier)) {
            throw new LogicException('Identifier for the hardware item DTO must be set explicitly.');
        }

        return $this->identifier;
    }

    /**
     * Sets hardware item identifier
     *
     * @param int $identifier Hardware item identifier
     *
     * @return void
     */
    public function setIdentifier(int $identifier): void
    {
        $this->identifier = $identifier;
    }

    /**
     * Returns hardware item name
     *
     * @return string
     */
    public function getName(): string
    {
        if (!is_string($this->name)) {
            throw new LogicException('Name for the hardware item DTO must be set explicitly.');
        }

        return $this->name;
    }

    /**
     * Sets hardware item name
     *
     * @param string $name Hardware item name
     *
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns URI with item information on the website
     *
     * @return string
     */
    public function getPageUri(): string
    {
        if (!is_string($this->pageUri)) {
            throw new LogicException('Page URI for the hardware item DTO must be set explicitly.');
        }

        return $this->pageUri;
    }

    /**
     * Sets URI with item information on the website
     *
     * @param string $pageUri Page URI with item information
     */
    public function setPageUri(string $pageUri): void
    {
        $this->pageUri = $pageUri;
    }
}
