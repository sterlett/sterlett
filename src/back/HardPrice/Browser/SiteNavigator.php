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
 * Opens the HardPrice website in the remote browser tab
 */
class SiteNavigator
{
    public function navigate(): PromiseInterface
    {
        // todo (gen 3)

        return reject(new RuntimeException('todo'));
    }
}
