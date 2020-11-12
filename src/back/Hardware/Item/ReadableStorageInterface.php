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

namespace Sterlett\Hardware\Item;

use Sterlett\Dto\Hardware\Item;
use Traversable;

/**
 * Holds hardware item data, acquired from the external sources, and provides read only access (an interface for data
 * consumers side)
 */
interface ReadableStorageInterface
{
    /**
     * Returns all hardware items from the storage
     *
     * @return Traversable<Item>|Item[]
     */
    public function all(): iterable;

    /**
     * Returns hardware item data by the given external identifier
     *
     * @param int $identifier Hardware item identifier
     *
     * @return Item|null
     */
    public function get(int $identifier): ?Item;
}
