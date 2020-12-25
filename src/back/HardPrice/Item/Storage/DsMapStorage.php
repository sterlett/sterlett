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

namespace Sterlett\HardPrice\Item\Storage;

use Ds\Map;
use RuntimeException;
use Sterlett\Dto\Hardware\Item;
use Sterlett\HardPrice\Item\StorageInterface;

/**
 * Holds hardware item data, acquired from the external sources, in the memory, using Ds\Map structure
 */
class DsMapStorage implements StorageInterface
{
    /**
     * Contains id-item pairs, representing information about hardware, acquired from the external sources
     *
     * @var Map
     */
    private Map $_map;

    /**
     * DsMapStorage constructor.
     */
    public function __construct()
    {
        $this->_map = new Map();
    }

    /**
     * {@inheritDoc}
     */
    public function all(): iterable
    {
        $pairSequence = $this->_map->values();

        return $pairSequence;
    }

    /**
     * {@inheritDoc}
     */
    public function require(int $itemIdentifier): Item
    {
        if (!$this->_map->hasKey($itemIdentifier)) {
            $notFoundExceptionMessage = sprintf(
                "Hardware item with external identifier '%s' is not found.",
                $itemIdentifier
            );

            throw new RuntimeException($notFoundExceptionMessage);
        }

        $hardwareItem = $this->_map->get($itemIdentifier);

        return $hardwareItem;
    }

    /**
     * {@inheritDoc}
     */
    public function add(Item $item): void
    {
        $itemIdentifier = $item->getIdentifier();

        $this->_map->put($itemIdentifier, $item);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->_map->clear();
    }
}
