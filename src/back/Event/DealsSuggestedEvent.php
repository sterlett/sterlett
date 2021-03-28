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
 * Describes an event, when the new deals are ready for the async handlers
 */
class DealsSuggestedEvent extends Event implements DeferredEventInterface
{
    use DeferredEventTrait;

    /**
     * Event name
     *
     * @var string
     */
    public const NAME = 'app.event.deals_suggested';

    /**
     * Deals data for the async handlers
     *
     * @var iterable
     */
    private iterable $deals;

    /**
     * DealsSuggestedEvent constructor.
     */
    public function __construct()
    {
        $this->deals     = [];
        $this->_deferred = new Deferred();
    }

    /**
     * Sets deals data
     *
     * @param iterable $deals Deals data
     *
     * @return void
     */
    public function setDeals(iterable $deals): void
    {
        $this->deals = $deals;
    }

    /**
     * Returns deals data, attached to the event
     *
     * @return iterable
     */
    public function getDeals(): iterable
    {
        return $this->deals;
    }
}
