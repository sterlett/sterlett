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

namespace Sterlett\Hardware\Benchmark;

use React\Promise\PromiseInterface;
use Sterlett\Dto\Hardware\Benchmark;
use Traversable;

/**
 * Retrieves hardware benchmark data
 */
interface ProviderInterface
{
    /**
     * Returns a promise that resolves into a list with benchmarks for the configured hardware category (async approach
     * by default). Adapters for environments with blocking I/O should return a fulfilled promise.
     *
     * @return PromiseInterface<Traversable<Benchmark>|Benchmark[]>
     */
    public function getBenchmarks(): PromiseInterface;
}
