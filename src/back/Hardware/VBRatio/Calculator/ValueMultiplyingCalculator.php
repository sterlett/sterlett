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

namespace Sterlett\Hardware\VBRatio\Calculator;

use Sterlett\Hardware\VBRatio\CalculatorInterface;

/**
 * This implementation allows to adjust the resulting V/B ratio value, as an additional step for the original
 * calculation algorithm
 */
class ValueMultiplyingCalculator implements CalculatorInterface
{
    /**
     * Base implementation for the V/B ratio calculation algorithm
     *
     * @var CalculatorInterface
     */
    private CalculatorInterface $ratioCalculator;

    /**
     * A multiplier, to regulate number of digits BEFORE the decimal place in the V/B ratio value (e.g. '100.00')
     *
     * @var string
     */
    private string $valueMultiplier;

    /**
     * Defines the desired number of digits AFTER the decimal place
     *
     * @var int
     */
    private int $valueScale;

    /**
     * ValueMultiplyingCalculator constructor.
     *
     * @param CalculatorInterface $ratioCalculator Base implementation for the V/B ratio calculation algorithm
     * @param string              $valueMultiplier A multiplier, to regulate number of digits BEFORE the decimal place
     * @param int                 $valueScale      Defines the desired number of digits AFTER the decimal place
     */
    public function __construct(CalculatorInterface $ratioCalculator, string $valueMultiplier, int $valueScale)
    {
        $this->ratioCalculator = $ratioCalculator;
        $this->valueMultiplier = $valueMultiplier;
        $this->valueScale      = $valueScale;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateRatio(iterable $hardwarePrices, string $benchmarkValue): string
    {
        $ratioValue = $this->ratioCalculator->calculateRatio($hardwarePrices, $benchmarkValue);

        $valueMultiplied = bcmul($ratioValue, $this->valueMultiplier, $this->valueScale);

        return $valueMultiplied;
    }
}
