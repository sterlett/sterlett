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

use Sterlett\Dto\Hardware\Price;

/**
 * A storage with price records for the hardware items
 */
class Repository
{
    /**
     * Repository constructor.
     */
    public function __construct()
    {
        // todo: inject mysql connection
    }

    public function findAll(): iterable
    {
        // todo

        return [];
    }

    public function save(Price $hardwarePrice): void
    {
        // todo
    }
}
