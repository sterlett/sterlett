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

use React\Promise\PromiseInterface;
use Sterlett\Hardware\VBRatioInterface;

/**
 * Resolves a Value/Benchmark ratio for available hardware items (async approach)
 */
interface ProviderInterface
{
    /**
     * Returns a promise that resolves to a collection with V/B ratio calculation results for the available hardware
     * items.
     *
     * The resulting collection is expected as an instance of Traversable<VBRatioInterface> or VBRatioInterface[],
     * with hardware identifier as a key.
     *
     * @return PromiseInterface<iterable>
     *
     * @see VBRatioInterface
     */
    public function getRatios(): PromiseInterface;
}
