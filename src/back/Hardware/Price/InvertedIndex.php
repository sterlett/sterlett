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

    /**
     * @param string $word
     *
     * @return InvertedIndexEntry[]|null
     */
    public function get(string $word): ?array
    {
        /** @var Set $wordIndexes */
        $wordIndexes = $this->_indexesByWord->get($word, null);

        if (null === $wordIndexes) {
            return null;
        }

        $indexesActualized = $wordIndexes->filter(
            function (InvertedIndexEntry $indexEntry) {
                return !$this->_indexesIgnored->contains($indexEntry->index);
            }
        );

        $indexArray = $indexesActualized->toArray();

        return $indexArray;
    }

    public function add(string $word, int $index, int $priority = 0): void
    {
        /** @var Set|null $wordIndexes */
        $wordIndexes = $this->_indexesByWord->get($word, null);

        if (null === $wordIndexes) {
            $wordIndexes = new Set();

            $this->_indexesByWord->put($word, $wordIndexes);
        }

        $indexEntry = new InvertedIndexEntry($index, $priority);
        $wordIndexes->add($indexEntry);

        $this->_indexesIgnored->remove($index);
    }

    public function removeIndex(int $index): void
    {
        $this->_indexesIgnored->add($index);
    }
}

final class InvertedIndexEntry
{
    public int $index;

    public int $priority;

    public function __construct(int $index, int $priority)
    {
        $this->index    = $index;
        $this->priority = $priority;
    }
}
