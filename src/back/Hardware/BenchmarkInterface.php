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

namespace Sterlett\Hardware;

/**
 * Represents a single benchmark record for the specific hardware
 */
interface BenchmarkInterface
{
    /**
     * Returns hardware name
     *
     * @return string
     */
    public function getHardwareName(): string;

    /**
     * Returns benchmark value
     *
     * @return string
     */
    public function getValue(): string;
}
