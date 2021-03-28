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

use Sterlett\Event\VBRatiosCalculatedEvent;
use Sterlett\Hardware\VBRatio\Packer as VBRatioPacker;
use Sterlett\Request\Handler\HardwareMarkHandler;

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
     * Converts a V/B ratio data from the object collection format to a raw string for HTTP handlers
     *
     * @var VBRatioPacker
     */
    private VBRatioPacker $ratioPacker;

    /**
     * CpuMarkAcceptanceListener constructor.
     *
     * @param HardwareMarkHandler $hardwareMarkHandler Handles requests for information about "price/benchmark" ratio
     * @param VBRatioPacker       $ratioPacker         Converts a V/B ratios from the collection format to a raw string
     */
    public function __construct(HardwareMarkHandler $hardwareMarkHandler, VBRatioPacker $ratioPacker)
    {
        $this->hardwareMarkHandler = $hardwareMarkHandler;
        $this->ratioPacker         = $ratioPacker;
    }

    /**
     * Captures a V/B ratio list event and runs handler feeding logic
     *
     * @param mixed $data Represents a list with hardware statistics from the CPU category
     *
     * @return void
     */
    public function onVBRatiosCalculated($data): void
    {
        $this->feedHandler($data);
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
        if ($data instanceof VBRatiosCalculatedEvent) {
            $ratios = $data->getRatios();

            // converting data to the appropriate format to "feed" a handler.
            // todo: extract all additional logic to the separate service
            // todo: try-catch and log possible errors (omitted)
            $dataString = $this->ratioPacker->packRatios($ratios);

            $this->hardwareMarkHandler->resetState(HardwareMarkHandler::ACTION_CPU_LIST);
        } else {
            $dataString = (string) $data;

            // note: state reset is not available.
        }

        $this->hardwareMarkHandler->addCpuData($dataString);
    }
}
