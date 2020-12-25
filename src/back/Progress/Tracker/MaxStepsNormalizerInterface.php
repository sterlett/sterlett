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

namespace Sterlett\Progress\Tracker;

/**
 * Applies normalization rules to the given max progress steps count
 */
interface MaxStepsNormalizerInterface
{
    /**
     * Returns count of max steps that will be used for progress tracking, normalized by the applied rules
     *
     * @param int $stepCountMax Count of max steps for progress tracking
     *
     * @return int
     */
    public function normalize(int $stepCountMax): int;
}
