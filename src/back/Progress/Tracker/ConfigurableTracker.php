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

namespace Sterlett\Progress\Tracker;

use Sterlett\Progress\TrackerInterface;

/**
 * Tracks progress of task/activity using configured normalizers and validators for steps counting
 */
class ConfigurableTracker implements TrackerInterface
{
    /**
     * Tracks activity progress using steps
     *
     * @var TrackerInterface
     */
    private TrackerInterface $progressTracker;

    /**
     * Applies normalization rules to the given max progress steps count
     *
     * @var MaxStepsNormalizerInterface
     */
    private MaxStepsNormalizerInterface $maxStepsNormalizer;

    /**
     * ConfigurableTracker constructor.
     *
     * @param TrackerInterface            $progressTracker    Tracks activity progress using steps
     * @param MaxStepsNormalizerInterface $maxStepsNormalizer Applies normalization rules to the given max progress
     *                                                        steps count
     */
    public function __construct(TrackerInterface $progressTracker, MaxStepsNormalizerInterface $maxStepsNormalizer)
    {
        $this->progressTracker    = $progressTracker;
        $this->maxStepsNormalizer = $maxStepsNormalizer;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxSteps(int $stepCountMax): void
    {
        $stepCountMaxNormalized = $this->maxStepsNormalizer->normalize($stepCountMax);

        $this->progressTracker->setMaxSteps($stepCountMaxNormalized);
    }

    /**
     * {@inheritDoc}
     */
    public function start(): void
    {
        $this->progressTracker->start();
    }

    /**
     * {@inheritDoc}
     */
    public function advance(int $stepCount = 1): void
    {
        $this->progressTracker->advance($stepCount);
    }

    /**
     * {@inheritDoc}
     */
    public function finish(): void
    {
        $this->progressTracker->finish();
    }
}
