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
use RingCentral\Psr7\Response;
use function React\Promise\resolve;

class PriceRequester
{
    public function requestPrices(iterable $hardwareIdentifiers): PromiseInterface
    {
        // todo: request prices using authentication.

        return resolve(new Response());
    }

    public function setAuthentication(Authentication $authentication): void
    {
        // todo: accept authentication
    }
}
