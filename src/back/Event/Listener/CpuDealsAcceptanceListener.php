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

use Sterlett\Event\DealsSuggestedEvent;
use Sterlett\Request\Handler\HardwareMarkHandler;

/**
 * Listens events from CPU stats providers (deals data) and updates the async handler
 */
class CpuDealsAcceptanceListener
{
    /**
     * Handles requests for information about "price/benchmark" ratio for the different hardware categories
     *
     * @var HardwareMarkHandler
     */
    private HardwareMarkHandler $hardwareMarkHandler;

    /**
     * CpuDealsAcceptanceListener constructor.
     *
     * @param HardwareMarkHandler $hardwareMarkHandler Handles requests for information about "price/benchmark" ratio
     */
    public function __construct(HardwareMarkHandler $hardwareMarkHandler)
    {
        $this->hardwareMarkHandler = $hardwareMarkHandler;
    }

    /**
     * Captures a deals dataset and runs handler feeding logic
     *
     * @param mixed $data Represents a list with hardware statistics from the CPU category
     *
     * @return void
     */
    public function onDealsSuggested($data): void
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
        if ($data instanceof DealsSuggestedEvent) {
            $deals = $data->getDeals();

            // converting data to the appropriate format.
            // todo: extract all additional logic to the separate service
            $dataString = json_encode($deals);

            $this->hardwareMarkHandler->resetState(HardwareMarkHandler::ACTION_CPU_DEALS);
        } else {
            $dataString = (string) $data;

            // note: state reset is not available.
        }

        $this->hardwareMarkHandler->addCpuDealsData($dataString);
    }
}
