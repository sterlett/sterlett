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

use RuntimeException;
use Sterlett\Hardware\BenchmarkInterface;
use Traversable;

/**
 * Retrieves hardware benchmark data in the traditional, blocking I/O way
 */
interface BlockingProviderInterface
{
    /**
     * Returns a list with benchmarks for the configured hardware category
     *
     * @return Traversable<BenchmarkInterface>|BenchmarkInterface[]
     *
     * @throws RuntimeException Whenever an error is raised during benchmark extracting, with previous context
     */
    public function getBenchmarks(): iterable;
}
