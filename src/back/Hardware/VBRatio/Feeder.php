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
use RuntimeException;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventDispatcher;
use Sterlett\Event\Listener\CpuMarkAcceptanceListener;
use Sterlett\Event\VBRatiosEmittedEvent;
use Sterlett\Hardware\VBRatioInterface;
use Throwable;
use Traversable;

/**
 * Emits a list with V/B ranks for the available hardware items (using a configured provider).
 *
 * It serves data actualization for the async HTTP handlers.
 *
 * @see CpuMarkAcceptanceListener
 */
class Feeder
{
    /**
     * Provides V/B ratio lists
     *
     * @var ProviderInterface
     */
    private ProviderInterface $ratioProvider;

    /**
     * Provides hooks on domain-specific lifecycles by dispatching events
     *
     * @var DeferredEventDispatcher
     */
    private DeferredEventDispatcher $eventDispatcher;

    /**
     * Feeder constructor.
     *
     * @param ProviderInterface       $ratioProvider   Provides V/B ratio lists
     * @param DeferredEventDispatcher $eventDispatcher Provides hooks on domain-specific lifecycles
     */
    public function __construct(ProviderInterface $ratioProvider, DeferredEventDispatcher $eventDispatcher)
    {
        $this->ratioProvider   = $ratioProvider;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Returns a promise that will be resolved when the V/B ratio list "feed" operation is complete
     *
     * @return PromiseInterface<null>
     */
    public function emitRatios(): PromiseInterface
    {
        $ratioListTransferPromise = $this->ratioProvider
            // calling in a responsible provider.
            ->getRatios()
            // transferring data to the async handlers for distribution via HTTP.
            ->then(fn (iterable $ratios) => $this->transferRatios($ratios))
        ;

        $ratioListTransferPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to dispatch a V/B ratio list (feeder).', 0, $rejectionReason);
            }
        );

        return $ratioListTransferPromise;
    }

    /**
     * Calls a dispatcher for transferring V/B ratio dataset to the async handlers
     *
     * @param Traversable<VBRatioInterface>|VBRatioInterface[] $ratios V/B ratio records
     *
     * @return PromiseInterface<null>
     */
    private function transferRatios(iterable $ratios): PromiseInterface
    {
        $ratioListEmittedEvent = new VBRatiosEmittedEvent();
        $ratioListEmittedEvent->setRatios($ratios);

        $this->eventDispatcher->dispatch($ratioListEmittedEvent, VBRatiosEmittedEvent::NAME);

        $eventPropagationPromise = $ratioListEmittedEvent->getPromise();

        return $eventPropagationPromise;
    }
}
