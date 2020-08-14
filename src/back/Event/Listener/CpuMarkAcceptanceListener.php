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

namespace Sterlett\Event\Listener;

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
     * CpuMarkAcceptanceListener constructor.
     *
     * @param HardwareMarkHandler $hardwareMarkHandler Handles requests for information about "price/benchmark" ratio
     */
    public function __construct(HardwareMarkHandler $hardwareMarkHandler)
    {
        $this->hardwareMarkHandler = $hardwareMarkHandler;
    }

    /**
     * Sets CPU statistics data to the request handler
     *
     * @param string $data Represents a list with hardware statistics from the CPU category
     *
     * @return void
     */
    public function onCpuMarkReceived(string $data): void
    {
        $this->hardwareMarkHandler->addCpuData($data);
    }
}
