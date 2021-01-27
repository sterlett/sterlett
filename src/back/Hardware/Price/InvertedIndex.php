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

namespace Sterlett\Hardware\Price;

use Ds\Map;
use Ds\Set;

/**
 * todo: unit test
 */
class InvertedIndex
{
    /**
     * Contains sets with price record identifiers, keyed by the item name parts (words)
     *
     * @var Map
     */
    private Map $_indexesByWord;

    private Set $_indexesIgnored;

    public function __construct()
    {
        $this->_indexesByWord  = new Map();
        $this->_indexesIgnored = new Set();
    }

    public function get(string $word): ?array
    {
        /** @var Set $wordIndexes */
        $wordIndexes = $this->_indexesByWord->get($word, null);

        if (null === $wordIndexes) {
            return null;
        }

        $indexesActualized = $wordIndexes->diff($this->_indexesIgnored);

        $indexArray = $indexesActualized->toArray();

        return $indexArray;
    }

    public function add(string $word, int $index): void
    {
        /** @var Set|null $wordIndexes */
        $wordIndexes = $this->_indexesByWord->get($word, null);

        if (null === $wordIndexes) {
            $wordIndexes = new Set();

            $this->_indexesByWord->put($word, $wordIndexes);
        }

        $wordIndexes->add($index);

        $this->_indexesIgnored->remove($index);
    }

    public function remove(int $index): void
    {
        $this->_indexesIgnored->add($index);
    }
}
