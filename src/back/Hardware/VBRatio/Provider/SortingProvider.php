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

namespace Sterlett\Hardware\VBRatio\Provider;

use Sterlett\Hardware\VBRatio\BlockingProviderInterface;
use Sterlett\Hardware\VBRatioInterface;

/**
 * Provides a sorted collection of the Value/Benchmark ratio records (by value).
 *
 * This provider will buffer its sources to perform data analysis and, therefore, implemented as a blocking mixin.
 */
class SortingProvider implements BlockingProviderInterface
{
    /**
     * Resolves a Value/Benchmark ratio for available hardware items (sync approach)
     *
     * @var BlockingProviderInterface
     */
    private BlockingProviderInterface $ratioProvider;

    /**
     * SortingProvider constructor.
     *
     * @param BlockingProviderInterface $ratioProvider Base implementation for the V/B ratio provider (sync approach)
     */
    public function __construct(BlockingProviderInterface $ratioProvider)
    {
        $this->ratioProvider = $ratioProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getRatios(): iterable
    {
        $ratios = $this->ratioProvider->getRatios();

        // downcasting from iterable (evaluating iterators/generators) to run a native sort.
        $ratioArray = [...$ratios];

        usort(
            $ratioArray,
            function (VBRatioInterface $left, VBRatioInterface $right) {
                return $right->getValue() <=> $left->getValue();
            }
        );

        return $ratioArray;
    }
}
