<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020-2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Event\Listener;

use RuntimeException;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventInterface;
use Sterlett\Event\VBRatiosEmittedEvent;
use Sterlett\Request\Handler\HardwareMarkHandler;
use Throwable;

/**
 * Listens events from CPU stats providers and transfers data to the handler for distribution
 */
class CpuMarkAcceptanceListener
{
    /**
     * Handles requests for information about "price/benchmark" ratio for the different hardware categories
     *
     * @var HardwareMarkHandler
     */
    private HardwareMarkHandler $hardwareMarkHandler;

    /**
     * CpuMarkAcceptanceListener constructor.
     *
     * @param HardwareMarkHandler $hardwareMarkHandler Handles requests for information about "price/benchmark" ratio
     */
    public function __construct(HardwareMarkHandler $hardwareMarkHandler)
    {
        $this->hardwareMarkHandler = $hardwareMarkHandler;
    }

    /**
     * Captures a V/B ratio list event and runs handler feeding logic
     *
     * @param mixed $data Represents a list with hardware statistics from the CPU category
     *
     * @return void
     */
    public function onCpuMarkReceived($data): void
    {
        // for Evenement events and raw data streaming.
        if (!$data instanceof DeferredEventInterface) {
            $this->feedHandler($data);

            return;
        }

        // for deferred psr-14 events.
        $dispatchingDeferred = $data->getDeferred();

        try {
            $this->feedHandler($data);

            $dispatchingDeferred->resolve($data);
        } catch (Throwable $exception) {
            $reason = new RuntimeException('Unable to feed a handler with V/B ratio data', 0, $exception);

            $dispatchingDeferred->reject($reason);
        }
    }

    /**
     * Sets CPU statistics data for the request handler
     *
     * @param mixed $data Represents a list with hardware statistics from the CPU category
     *
     * @return void
     */
    private function feedHandler($data): void
    {
        if ($data instanceof VBRatiosEmittedEvent) {
            $dataString = $data->getRatioData();

            $this->hardwareMarkHandler->resetState();
        } else {
            $dataString = (string) $data;

            // note: state reset is not available.
        }

        $this->hardwareMarkHandler->addCpuData($dataString);
    }
}
