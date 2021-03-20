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

namespace Sterlett\Hardware\Price\Collector;

use ArrayIterator;
use IteratorIterator;
use Sterlett\Hardware\Price\BlacklistSellerIterator;
use Sterlett\Hardware\Price\CollectorInterface;
use Traversable;

/**
 * Picks a price record from the given collection for the further processing only if a seller identifier is not listed
 * in the configured set
 */
class BlacklistSellerCollector implements CollectorInterface
{
    /**
     * An array of seller identifiers to ignore by the filtering condition
     *
     * @var array
     */
    private array $sellerIdentifiers;

    /**
     * BlacklistSellerCollector constructor.
     *
     * @param array $sellerIdentifiers An array of seller identifiers to ignore by the filtering condition
     */
    public function __construct(array $sellerIdentifiers)
    {
        $this->sellerIdentifiers = $sellerIdentifiers;
    }

    /**
     * {@inheritDoc}
     */
    public function collect(iterable $prices): iterable
    {
        if ($prices instanceof Traversable) {
            $priceIterator = new IteratorIterator($prices);
        } else {
            $priceIterator = new ArrayIterator($prices);
        }

        return new BlacklistSellerIterator($priceIterator, $this->sellerIdentifiers);
    }
}
