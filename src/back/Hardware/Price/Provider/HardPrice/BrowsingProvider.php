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

namespace Sterlett\Hardware\Price\Provider\HardPrice;

use React\Promise\PromiseInterface;
use Sterlett\Hardware\Price\ProviderInterface;
use function React\Promise\resolve;

/**
 * Gen 3 algorithm for price data retrieving from the HardPrice website.
 *
 * Emulates user behavior while traversing site pages using headless browser API.
 *
 * todo: move to actual headless mode (wrap_chrome_binary and docker-compose.yml template)
 */
class BrowsingProvider implements ProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        // todo: gen 3 algo (async webdriver)

        return resolve([]);
    }
}
