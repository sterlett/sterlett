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

use Sterlett\Hardware\PriceInterface;
use Traversable;

/**
 * Takes a set of price DTOs and applies filtering logic
 */
interface CollectorInterface
{
    /**
     * Returns a traversable collection of prices, filtered by the specific condition
     *
     * @param iterable $prices A set of prices for filtering
     *
     * @return Traversable<PriceInterface>|PriceInterface[]
     */
    public function collect(iterable $prices): iterable;
}
