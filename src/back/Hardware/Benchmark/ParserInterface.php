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

use Sterlett\Hardware\BenchmarkInterface;
use Traversable;

/**
 * Transforms raw benchmark data from the external resource to the list of application-level DTOs
 */
interface ParserInterface
{
    /**
     * Returns an iterable list with benchmark objects, extracted from the given raw data
     *
     * @param string $data Raw benchmark data from the external resource
     *
     * @return Traversable<BenchmarkInterface>|BenchmarkInterface[]
     */
    public function parse(string $data): iterable;
}
