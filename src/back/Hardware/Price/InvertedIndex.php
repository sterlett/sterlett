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
 * Data structure to map hardware name parts (words) with price records, which contains them
 *
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

    /**
     * A collection to ignore "removed" indexes
     *
     * @var Set
     */
    private Set $_indexesIgnored;

    /**
     * InvertedIndex constructor.
     */
    public function __construct()
    {
        $this->_indexesByWord  = new Map();
        $this->_indexesIgnored = new Set();
    }

    /**
     * Returns all indexes of price record buffer, which are related to the given word (hardware name part)
     *
     * @param string $word A hardware name part
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

    /**
     * Attaches an {index} with specified {priority} to the given {word}
     *
     * @param string $word     A hardware name part
     * @param int    $index    A numeric index in the price records buffer
     * @param int    $priority An additional payload to determine index priority within its set (optional)
     *
     * @return void
     */
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

    /**
     * Removes an index from the mappings
     *
     * @param int $index A numeric index in the price records buffer
     *
     * @return void
     */
    public function removeIndex(int $index): void
    {
        $this->_indexesIgnored->add($index);
    }
}

/**
 * Internal object that represents an entry of the inverted index
 */
final class InvertedIndexEntry
{
    /**
     * Index value
     *
     * @var int
     */
    public int $index;

    /**
     * Index priority
     *
     * @var int
     */
    public int $priority;

    /**
     * InvertedIndexEntry constructor.
     *
     * @param int $index    Index value (a reference to the price record in the buffer)
     * @param int $priority Priority among other indexes (for the same word)
     */
    public function __construct(int $index, int $priority)
    {
        $this->index    = $index;
        $this->priority = $priority;
    }
}
