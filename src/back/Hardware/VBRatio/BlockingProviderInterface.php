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

namespace Sterlett\Hardware\VBRatio;

use Sterlett\Hardware\VBRatioInterface;
use Traversable;

/**
 * Resolves a Value/Benchmark ratio for available hardware items (traditional, blocking I/O way)
 */
interface BlockingProviderInterface
{
    /**
     * Returns a collection with V/B ratio calculation results for the configured hardware category
     *
     * @return Traversable<VBRatioInterface>|VBRatioInterface[]
     */
    public function getRatios(): iterable;
}
