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

use AppendIterator;
use ArrayIterator;
use Iterator;
use IteratorIterator;
use Traversable;

/**
 * Iterates price collection and accumulates values for the same keys (hardware identifiers) into a single sequence
 */
class MergingIterator implements Iterator
{
    /**
     * Yields price data collections, keyed by the related identifiers for aggregation
     *
     * @var Iterator
     */
    private iterable $priceDataIterator;

    /**
     * Holds current key for the merging iteration (hardware identifier)
     *
     * @var int|null
     */
    private ?int $_aggregationKey;

    /**
     * MergingIterator constructor.
     *
     * @param Iterator $priceDataIterator Yields hardware price data collections, keyed by the related identifiers
     */
    public function __construct(Iterator $priceDataIterator)
    {
        $this->priceDataIterator = $priceDataIterator;

        $this->_aggregationKey = null;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        $priceListMerged = new AppendIterator();

        for (; $this->priceDataIterator->valid();) {
            $hardwareIdentifier = $this->priceDataIterator->key();

            if ($this->_aggregationKey !== $hardwareIdentifier) {
                break;
            }

            $hardwarePrices = $this->priceDataIterator->current();

            if ($hardwarePrices instanceof Traversable) {
                $priceListIterator = new IteratorIterator($hardwarePrices);
            } else {
                $priceListIterator = new ArrayIterator($hardwarePrices);
            }

            $priceListMerged->append($priceListIterator);

            $this->priceDataIterator->next();
        }

        return $priceListMerged;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        for (; $this->priceDataIterator->valid();) {
            $hardwareIdentifier = $this->priceDataIterator->key();

            if ($this->_aggregationKey !== $hardwareIdentifier) {
                $this->_aggregationKey = $hardwareIdentifier;

                break 1;
            }

            $this->priceDataIterator->next();
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
        return $this->priceDataIterator->valid();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->priceDataIterator->rewind();

        $this->_aggregationKey = $this->priceDataIterator->key();
    }
}
