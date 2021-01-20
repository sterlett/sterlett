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

namespace Sterlett\Hardware\Benchmark\Collector;

use ArrayIterator;
use IteratorIterator;
use Sterlett\Hardware\Benchmark\CollectorInterface;
use Sterlett\Hardware\Benchmark\ValueThresholdIterator;
use Traversable;

/**
 * Picks only benchmarks with a result value greater than (or equal to) the configured threshold
 */
class ValueThresholdCollector implements CollectorInterface
{
    /**
     * A numeric threshold value
     *
     * @var string
     */
    private string $valueThreshold;

    /**
     * ValueThresholdCollector constructor.
     *
     * @param string $valueThreshold A numeric threshold value
     */
    public function __construct(string $valueThreshold)
    {
        $this->valueThreshold = $valueThreshold;
    }

    /**
     * {@inheritDoc}
     */
    public function collect(iterable $benchmarks): iterable
    {
        if ($benchmarks instanceof Traversable) {
            $benchmarkIterator = new IteratorIterator($benchmarks);
        } else {
            $benchmarkIterator = new ArrayIterator($benchmarks);
        }

        return new ValueThresholdIterator($benchmarkIterator, $this->valueThreshold);
    }
}
