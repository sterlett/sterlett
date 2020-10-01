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

namespace Sterlett\Dto\Hardware;

use LogicException;
use Sterlett\Hardware\BenchmarkInterface;

/**
 * Data context for a single benchmark from the benchmark provider
 */
final class Benchmark implements BenchmarkInterface
{
    /**
     * Hardware name
     *
     * @var string|null
     */
    private ?string $hardwareName;

    /**
     * Benchmark result value
     *
     * @var string|null
     */
    private ?string $value;

    /**
     * Benchmark constructor.
     */
    public function __construct()
    {
        $this->hardwareName = null;
        $this->value        = null;
    }

    /**
     * {@inheritDoc}
     */
    public function getHardwareName(): string
    {
        if (!is_string($this->hardwareName)) {
            throw new LogicException('Hardware name for the benchmark DTO must be set explicitly.');
        }

        return $this->hardwareName;
    }

    /**
     * Sets hardware name
     *
     * @param string $hardwareName Hardware name
     *
     * @return void
     */
    public function setHardwareName(string $hardwareName): void
    {
        $this->hardwareName = $hardwareName;
    }

    /**
     * {@inheritDoc}
     */
    public function getValue(): string
    {
        if (!is_string($this->value)) {
            throw new LogicException('Value for the benchmark DTO must be set explicitly.');
        }

        return $this->value;
    }

    /**
     * Sets benchmark value
     *
     * @param string $value Benchmark value
     *
     * @return void
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
