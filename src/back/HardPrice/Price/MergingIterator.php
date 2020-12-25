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

namespace Sterlett\HardPrice\Price;

use Iterator;
use Sterlett\HardPrice\Price\Collector\MergingCollector;

/**
 * Iterates price collection and accumulates values for the same keys (hardware identifiers) into a single sequence.
 *
 * Should not be used in the async environment due to its buffering/blocking behavior.
 *
 * @see MergingCollector
 */
class MergingIterator implements Iterator
{
    /**
     * Yields price DTOs, keyed by the related hardware identifiers
     *
     * @var Iterator
     */
    private iterable $priceIterator;

    /**
     * Holds current key for the merging iteration (hardware identifier)
     *
     * @var int|null
     */
    private ?int $_aggregationKey;

    /**
     * Holds current values for the iteration (a collection with merged prices for the same hardware identifier)
     *
     * @var array|null
     */
    private ?array $_priceListAggregated;

    /**
     * MergingIterator constructor.
     *
     * @param Iterator $priceIterator Yields price DTOs, keyed by the related hardware identifiers
     */
    public function __construct(Iterator $priceIterator)
    {
        $this->priceIterator = $priceIterator;

        $this->_aggregationKey      = $this->priceIterator->key();
        $this->_priceListAggregated = null;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        if (null !== $this->_priceListAggregated) {
            return $this->_priceListAggregated;
        }

        for (; $this->priceIterator->valid();) {
            $hardwareIdentifier = $this->priceIterator->key();

            if ($this->_aggregationKey !== $hardwareIdentifier) {
                break;
            }

            $hardwarePrice                = $this->priceIterator->current();
            $this->_priceListAggregated[] = $hardwarePrice;

            $this->priceIterator->next();
        }

        return $this->_priceListAggregated;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        for (; $this->priceIterator->valid();) {
            $hardwareIdentifier = $this->priceIterator->key();

            if ($this->_aggregationKey !== $hardwareIdentifier) {
                $this->_aggregationKey      = $hardwareIdentifier;
                $this->_priceListAggregated = null;

                break 1;
            }

            $this->priceIterator->next();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->_aggregationKey;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return $this->priceIterator->valid();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->priceIterator->rewind();

        $this->_aggregationKey      = $this->priceIterator->key();
        $this->_priceListAggregated = null;
    }
}
