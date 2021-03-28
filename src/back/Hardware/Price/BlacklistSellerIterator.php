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

use Ds\Set;
use Iterator;
use Sterlett\Hardware\PriceInterface;

/**
 * Iterates price collection, but ignores sellers from the configured set
 */
class BlacklistSellerIterator implements Iterator
{
    /**
     * Yields price DTOs, the original collection
     *
     * @var Iterator<PriceInterface>
     */
    private iterable $priceIterator;

    /**
     * A set of seller identifiers to ignore by the filtering condition
     *
     * @var Set
     */
    private Set $_sellerIdSet;

    /**
     * BlacklistSellerIterator constructor.
     *
     * @param Iterator<PriceInterface> $priceIterator     Yields price DTOs, the original collection
     * @param array                    $sellerIdentifiers An array of seller identifiers to ignore by the condition
     */
    public function __construct(Iterator $priceIterator, array $sellerIdentifiers)
    {
        $this->priceIterator = $priceIterator;
        $this->_sellerIdSet  = new Set($sellerIdentifiers);

        $this->seek();
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->priceIterator->current();
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->priceIterator->next();

        $this->seek();
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->priceIterator->key();
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

        $this->seek();
    }

    /**
     * Moves a virtual pointer to the next element that is not listed in the configured seller-to-ignore set
     *
     * @return void
     */
    private function seek(): void
    {
        for (; $this->priceIterator->valid();) {
            /** @var PriceInterface $price */
            $price = $this->priceIterator->current();

            if ($this->isConditionApplied($price)) {
                $this->priceIterator->next();

                continue;
            }

            break;
        }
    }

    /**
     * Returns positive whenever a seller from the price record is listed in the configured set
     *
     * @param PriceInterface $price An element of the original collection to be checked
     *
     * @return bool true means the record should be rejected
     */
    private function isConditionApplied(PriceInterface $price): bool
    {
        $sellerIdentifier = $price->getSellerIdentifier();

        if ($this->_sellerIdSet->contains($sellerIdentifier)) {
            return true;
        }

        return false;
    }
}
