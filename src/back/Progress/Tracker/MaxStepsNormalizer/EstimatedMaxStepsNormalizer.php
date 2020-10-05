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

namespace Sterlett\Progress\Tracker\MaxStepsNormalizer;

use Sterlett\Progress\Tracker\MaxStepsNormalizerInterface;

/**
 * Sets a preconfigured, estimated max steps count for the task/activity as a fallback
 */
class EstimatedMaxStepsNormalizer implements MaxStepsNormalizerInterface
{
    /**
     * Estimated max steps for the task/activity
     *
     * @var int
     */
    private int $stepCountEstimated;

    /**
     * EstimatedMaxStepsNormalizer constructor.
     *
     * @param int $stepCountEstimated Estimated max steps for the task/activity
     */
    public function __construct(int $stepCountEstimated)
    {
        $this->stepCountEstimated = $stepCountEstimated;
    }

    /**
     * {@inheritDoc}
     */
    public function normalize(int $stepCountMax): int
    {
        if ($stepCountMax < 1) {
            return $this->stepCountEstimated;
        }

        return $stepCountMax;
    }
}
