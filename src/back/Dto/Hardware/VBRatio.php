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

namespace Sterlett\Dto\Hardware;

use LogicException;
use Sterlett\Hardware\BenchmarkInterface;
use Sterlett\Hardware\PriceInterface;
use Sterlett\Hardware\VBRatioInterface;

/**
 * Represents a single V/B calculation result for the specific hardware item
 *
 * todo: php80 - migrate to named constructor arguments instead of fluent setters & getter's checks
 */
final class VBRatio implements VBRatioInterface
{
    /**
     * Hardware price records, which are used in the calculation
     *
     * @var PriceInterface[]
     */
    private array $sourcePrices;

    /**
     * Benchmark results for the related hardware item
     *
     * @var BenchmarkInterface|null
     */
    private ?BenchmarkInterface $sourceBenchmark;

    /**
     * V/B rating value
     *
     * @var string|null
     */
    private ?string $value;

    /**
     * VBRatio constructor.
     */
    public function __construct()
    {
        $this->sourcePrices    = [];
        $this->sourceBenchmark = null;
        $this->value           = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourcePrices(): array
    {
        return $this->sourcePrices;
    }

    /**
     * Adds a given price record as a source record for the V/B ratio calculation DTO
     *
     * @param PriceInterface $price A price record
     *
     * @return void
     */
    public function addSourcePrice(PriceInterface $price): void
    {
        $this->sourcePrices[] = $price;
    }

    /**
     * {@inheritDoc}
     */
    public function getSourceBenchmark(): BenchmarkInterface
    {
        if (!$this->sourceBenchmark instanceof BenchmarkInterface) {
            throw new LogicException('Source benchmark for the V/B ratio DTO must be set explicitly.');
        }

        return $this->sourceBenchmark;
    }

    /**
     * Sets a given benchmark record as a source for the V/B ratio calculation DTO
     *
     * @param BenchmarkInterface $benchmark A benchmark record
     *
     * @return void
     */
    public function setSourceBenchmark(BenchmarkInterface $benchmark): void
    {
        $this->sourceBenchmark = $benchmark;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        if (!is_string($this->value)) {
            throw new LogicException('Value for the V/B ratio DTO must be set explicitly.');
        }

        return $this->value;
    }

    /**
     * Sets V/B rating value for the calculation DTO
     *
     * @param string $value Calculated V/B ratio value
     *
     * @return void
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
