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

namespace Sterlett;

/**
 * Executes code periodically, while the application is running
 */
interface RoutineInterface
{
    /**
     * Runs the defined logic periodically
     *
     * @return void
     */
    public function run(): void;
}
