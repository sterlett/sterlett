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

namespace Sterlett\Hardware\Deal;

use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\reject;

/**
 * Monitors hardware prices from the different sellers and suggests best deals, in terms of Price/Performance
 */
class Observer
{
    /**
     * Returns a promise that will be resolved when the service completes its deal analysis using the available hardware
     * prices and benchmark values
     *
     * @return PromiseInterface<null>
     */
    public function suggestDeals(): PromiseInterface
    {
        return reject(new RuntimeException('todo'));
    }
}
