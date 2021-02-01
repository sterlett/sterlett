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

namespace Sterlett\Hardware\Benchmark;

use Sterlett\Hardware\BenchmarkInterface;
use Traversable;

/**
 * Takes a set of benchmark DTOs and applies filtering logic
 */
interface CollectorInterface
{
    /**
     * Returns a traversable collection of benchmarks, filtered by the specific condition
     *
     * @param iterable $benchmarks A set of benchmarks for filtering
     *
     * @return Traversable<BenchmarkInterface>|BenchmarkInterface[]
     */
    public function collect(iterable $benchmarks): iterable;
}
