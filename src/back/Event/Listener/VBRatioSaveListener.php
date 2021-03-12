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

namespace Sterlett\Event\Listener;

use Sterlett\Event\VBRatiosEmittedEvent;
use Sterlett\Hardware\VBRatio\Saver as VBRatioSaver;
use Throwable;

/**
 * Listens events from V/B ratio providers and saves calculation results in the local storage
 */
class VBRatioSaveListener
{
    /**
     * Saves V/B ratio data in the local storage
     *
     * @var VBRatioSaver
     */
    private VBRatioSaver $ratioSaver;

    /**
     * VBRatioSaveListener constructor.
     *
     * @param VBRatioSaver $ratioSaver Saves V/B ratio data in the local storage
     */
    public function __construct(VBRatioSaver $ratioSaver)
    {
        $this->ratioSaver = $ratioSaver;
    }

    /**
     * Captures a V/B ratio list event and runs saving logic
     *
     * @param VBRatiosEmittedEvent $event A V/B ratio emitted event
     *
     * @return void
     */
    public function onVBRatiosReceived(VBRatiosEmittedEvent $event): void
    {
        $this->saveRatios($event);
    }

    /**
     * Saves calculated V/B ratios in the local storage
     *
     * @param VBRatiosEmittedEvent $event A V/B ratio emitted event
     *
     * @return void
     */
    private function saveRatios(VBRatiosEmittedEvent $event): void
    {
        $ratios = $event->getRatios();

        $saveConfirmationPromise = $this->ratioSaver->saveRatios($ratios);

        $saveConfirmationPromise->then(
            function (Throwable $rejectionReason) {
                // todo: handle reject
            }
        );
    }
}
