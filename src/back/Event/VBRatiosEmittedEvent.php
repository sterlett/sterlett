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

namespace Sterlett\Event;

use React\Promise\Deferred;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventInterface;
use Sterlett\Bridge\Symfony\Component\EventDispatcher\DeferredEventTrait;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Describes an event, when the V/B ratio feeder emits a fresh ratio dataset for the async handlers
 */
class VBRatiosEmittedEvent extends Event implements DeferredEventInterface
{
    use DeferredEventTrait;

    /**
     * Event name
     *
     * @var string
     */
    public const NAME = 'app.event.ratios_emitted';

    /**
     * V/B ratio data for the async handlers
     *
     * @var string
     */
    private string $ratioData;

    /**
     * VBRatiosEmittedEvent constructor.
     */
    public function __construct()
    {
        $this->ratioData = '';
        $this->_deferred = new Deferred();
    }

    /**
     * Sets V/B ratio data
     *
     * @param string $ratioData V/B ratio data for the async handlers
     *
     * @return void
     */
    public function setRatioData(string $ratioData): void
    {
        $this->ratioData = $ratioData;
    }

    /**
     * Returns a V/B ratio dataset, attached to the event
     *
     * @return string
     */
    public function getRatioData(): string
    {
        return $this->ratioData;
    }
}