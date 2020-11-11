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

namespace Sterlett\HardPrice\Store;

use RuntimeException;

/**
 * Maps an external identifier to the related store (seller)
 */
interface MapperInterface
{
    /**
     * Returns a unique textual label/slug for the hardware seller with the given identifier
     *
     * @param int $externalIdentifier Store identifier from the website
     *
     * @return string
     *
     * @throws RuntimeException whenever a store for the given identifier is not defined
     */
    public function requireSlug(int $externalIdentifier): string;
}
