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
use Sterlett\Hardware\VBRatio\BindingsUpdater as VBRatioBindingsUpdater;
use Throwable;

/**
 * Listens events from V/B ratio providers and saves price-benchmark bindings in the local storage
 */
class VBRatioBindingsUpdateListener
{
    /**
     * Saves bindings (price-benchmark) for the V/B ratio data
     *
     * @var VBRatioBindingsUpdater
     */
    private VBRatioBindingsUpdater $bindingsUpdater;

    /**
     * VBRatioBindingsUpdateListener constructor.
     *
     * @param VBRatioBindingsUpdater $bindingsUpdater Saves bindings (price-benchmark) for the V/B ratio data
     */
    public function __construct(VBRatioBindingsUpdater $bindingsUpdater)
    {
        $this->bindingsUpdater = $bindingsUpdater;
    }

    /**
     * Captures a V/B ratio list event and runs logic to save the price-benchmark bindings
     *
     * @param VBRatiosEmittedEvent $event A V/B ratio emitted event
     *
     * @return void
     */
    public function onVBRatiosReceived(VBRatiosEmittedEvent $event): void
    {
        $this->updateBindings($event);
    }

    /**
     * Updates price-benchmark bindings in the local storage
     *
     * @param VBRatiosEmittedEvent $event A V/B ratio emitted event
     *
     * @return void
     */
    private function updateBindings(VBRatiosEmittedEvent $event): void
    {
        $ratios = $event->getRatios();

        $updateConfirmationPromise = $this->bindingsUpdater->updateBindings($ratios);

        $updateConfirmationPromise->then(
            function (Throwable $rejectionReason) {
                // todo: handle reject
            }
        );
    }
}
