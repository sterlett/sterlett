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

namespace Sterlett\Hardware\Item\Storage;

use Ds\Map;
use Sterlett\Dto\Hardware\Item;
use Sterlett\Hardware\Item\StorageInterface;

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
    public function get(int $identifier): ?Item
    {
        if (!$this->_map->hasKey($identifier)) {
            return null;
        }

        $hardwareItem = $this->_map->get($identifier);

        return $hardwareItem;
    }

    /**
     * {@inheritDoc}
     */
    public function add(Item $item): void
    {
        $identifier = $item->getIdentifier();

        $this->_map->put($identifier, $item);
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->_map->clear();
    }
}
