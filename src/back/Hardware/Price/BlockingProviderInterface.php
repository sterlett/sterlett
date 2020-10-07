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

use RuntimeException;
use Sterlett\Hardware\PriceInterface;
use Traversable;

/**
 * Retrieves hardware prices in the traditional, blocking I/O way
 */
interface BlockingProviderInterface
{
    /**
     * Returns a list with price records for the configured hardware category
     *
     * @return Traversable<PriceInterface>|PriceInterface[]
     *
     * @throws RuntimeException Whenever an error is raised during price extracting, with context of the previous one
     */
    public function getPrices(): iterable;
}
