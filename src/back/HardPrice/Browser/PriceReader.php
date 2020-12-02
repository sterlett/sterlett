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

namespace Sterlett\HardPrice\Browser;

use React\Promise\PromiseInterface;
use RuntimeException;
use function React\Promise\reject;

/**
 * Opens a page with item prices in the remove browser and saves them for the browsing context
 */
class PriceReader
{
    public function readPrices(): PromiseInterface
    {
        // todo (gen 3)

        return reject(new RuntimeException('todo'));
    }
}
