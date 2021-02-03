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

namespace Sterlett\Hardware\VBRatio;

use React\Promise\PromiseInterface;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventDispatcher;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventInterface;
use Sterlett\Event\VBRatiosEmittedEvent;

/**
 * Emits a list with V/B ranks for the available hardware items (using a configured provider)
 */
class Feeder
{
    /**
     * Provides hooks on domain-specific lifecycles by dispatching events
     *
     * @var DeferredEventDispatcher
     */
    private DeferredEventDispatcher $eventDispatcher;

    /**
     * Feeder constructor.
     *
     * @param DeferredEventDispatcher $eventDispatcher Provides hooks on domain-specific lifecycles
     */
    public function __construct(DeferredEventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns a promise that will be resolved when the V/B ratio list feed operation is complete
     *
     * @return PromiseInterface<null>
     */
    public function emitRatios(): PromiseInterface
    {
        // todo

        $tmpData = <<<DATA
            {
                "items": [
                    {
                        "name": "AMD Ryzen 5 3600",
                        "image": "https://hardprice.ru/images/p/_/ryzen3000/ryzen-5-3600.jpg",
                        "prices": [
                            {
                                "type": "average",
                                "value": 156507013,
                                "currency": "RUB",
                                "precision": 4
                            }
                        ],
                        "vb_ratio": 87.76,
                        "benchmarks": [
                            {
                                "name": "PassMark",
                                "value": 17832
                            }
                        ]
                    }
                ]
            }
DATA;

        $ratioListEmittedEvent = new VBRatiosEmittedEvent();
        $ratioListEmittedEvent->setRatioData($tmpData);

        $this->eventDispatcher->dispatch($ratioListEmittedEvent, VBRatiosEmittedEvent::NAME);

        $dispatchingDeferred       = $ratioListEmittedEvent->getDeferred();
        $propagationStoppedPromise = $dispatchingDeferred->promise();

        return $propagationStoppedPromise;
    }
}
