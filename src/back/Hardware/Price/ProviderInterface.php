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

namespace Sterlett\Hardware\Price;

use React\Promise\PromiseInterface;
use Traversable;

/**
 * Retrieves hardware prices (async approach)
 */
interface ProviderInterface
{
    /**
     * Returns a promise that resolves to a collection with price records for the configured hardware category.
     *
     * An iterable element of resulting collection is a nested Traversable<PriceInterface> or PriceInterface[],
     * with hardware identifier as a key.
     *
     * todo: retval decomposition
     *
     * @return PromiseInterface<Traversable<iterable>>|iterable[]>
     */
    public function getPrices(): PromiseInterface;
}
