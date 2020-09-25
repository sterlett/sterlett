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

/**
 * Context of data for a single benchmark from the custom benchmark provider
 */
final class Benchmark
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
     * Returns hardware name
     *
     * @return string|null
     */
    public function getHardwareName(): ?string
    {
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
     * Returns benchmark value
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
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
