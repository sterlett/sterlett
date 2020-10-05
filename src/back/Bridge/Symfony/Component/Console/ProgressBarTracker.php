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

namespace Sterlett\Bridge\Symfony\Component\Console;

use Sterlett\Progress\TrackerInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Tracks progress of task/activity using Symfony's ProgressBar helper
 */
class ProgressBarTracker implements TrackerInterface
{
    /**
     * Renders progress output
     *
     * @var ProgressBar
     */
    private ProgressBar $progressBar;

    /**
     * ProgressBarTracker constructor.
     *
     * @param ProgressBar $progressBar Renders progress output
     */
    public function __construct(ProgressBar $progressBar)
    {
        $this->progressBar = $progressBar;
    }

    /**
     * {@inheritDoc}
     */
    public function setMaxSteps(int $stepCountMax): void
    {
        $this->progressBar->setMaxSteps($stepCountMax);
    }

    /**
     * {@inheritDoc}
     */
    public function start(): void
    {
        $this->progressBar->start();
    }

    /**
     * {@inheritDoc}
     */
    public function advance(int $stepCount = 1): void
    {
        $this->progressBar->advance($stepCount);
    }

    /**
     * {@inheritDoc}
     */
    public function finish(): void
    {
        $this->progressBar->finish();

        // ensure no output will remain after tracking is stopped.
        $this->progressBar->clear();
    }
}
