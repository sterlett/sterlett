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

use InvalidArgumentException;
use Iterator;
use Sterlett\Hardware\Benchmark\Collector\ValueThresholdCollector;
use Sterlett\Hardware\BenchmarkInterface;

/**
 * Iterates a benchmark collection, while rejecting values, which are lower than the configured threshold
 *
 * @see ValueThresholdCollector
 */
class ValueThresholdIterator implements Iterator
{
    /**
     * An original iterator for the benchmark collection
     *
     * @var Iterator<BenchmarkInterface>
     */
    private Iterator $benchmarkIterator;

    /**
     * A numeric string (e.g. '65733.75')
     *
     * @var string
     */
    private string $valueThreshold;

    /**
     * ValueThresholdIterator constructor.
     *
     * @param Iterator $benchmarkIterator An original iterator for the benchmark collection
     * @param string   $valueThreshold    A numeric value
     */
    public function __construct(Iterator $benchmarkIterator, string $valueThreshold)
    {
        $this->benchmarkIterator = $benchmarkIterator;

        if (!is_numeric($valueThreshold)) {
            throw new InvalidArgumentException('A threshold value must be valid numeric string.');
        }

        $this->valueThreshold = $valueThreshold;

        $this->seek();
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->benchmarkIterator->current();
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->benchmarkIterator->next();

        $this->seek();
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->benchmarkIterator->key();
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return $this->benchmarkIterator->valid();
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->benchmarkIterator->rewind();

        $this->seek();
    }

    /**
     * Moves a virtual pointer to the next element that is accepted by the value threshold condition
     *
     * @return void
     */
    private function seek(): void
    {
        for (; $this->benchmarkIterator->valid();) {
            /** @var BenchmarkInterface $benchmark */
            $benchmark = $this->benchmarkIterator->current();

            if ($this->isConditionApplied($benchmark)) {
                $this->benchmarkIterator->next();

                continue;
            }

            break;
        }
    }

    /**
     * Returns positive whenever value threshold condition is applied to the element of the original collection
     *
     * @param BenchmarkInterface $benchmark An element of the original collection to be checked
     *
     * @return bool true means the record should be rejected
     */
    private function isConditionApplied(BenchmarkInterface $benchmark): bool
    {
        $benchmarkValue = $benchmark->getValue();

        if (!is_numeric($benchmarkValue) || -1 === bccomp($benchmarkValue, $this->valueThreshold)) {
            return true;
        }

        return false;
    }
}
