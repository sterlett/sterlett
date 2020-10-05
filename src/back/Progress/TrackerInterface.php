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

namespace Sterlett\Progress;

/**
 * Tracks activity progress using steps
 */
interface TrackerInterface
{
    /**
     * Sets max steps count to report progress of tracking activity.
     *
     * Can be called before tracking is started or later, after {@link start()} method is called. If the given max
     * steps count is below 1, it should be considered as an infinity task.
     *
     * @param int $stepCountMax Count of max steps for progress reporting
     *
     * @return void
     */
    public function setMaxSteps(int $stepCountMax): void;

    /**
     * Start activity tracking
     *
     * @return void
     */
    public function start(): void;

    /**
     * Updates activity progress report with the given completed steps count
     *
     * @param int $stepCount Count of steps to report as "completed"
     *
     * @return void
     */
    public function advance(int $stepCount = 1): void;

    /**
     * Stops activity tracking
     *
     * @return void
     */
    public function finish(): void;
}
