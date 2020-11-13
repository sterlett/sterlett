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

use Sterlett\Dto\Hardware\Item;

/**
 * Holds hardware items data, acquired from the external sources, and provides both read and write access (for data
 * co-owners context)
 */
interface StorageInterface extends ReadableStorageInterface
{
    /**
     * Adds a hardware data instance to the storage
     *
     * @param Item $item Contains information for a single hardware item
     *
     * @return void
     */
    public function add(Item $item): void;

    /**
     * Removes all hardware items from the storage
     *
     * @return void
     */
    public function clear(): void;
}
