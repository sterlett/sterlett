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

namespace Sterlett\Bridge\Symfony\Component\EventDispatcher;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Encapsulates common internals for the deferred event implementations.
 *
 * Note: ensure a deferred instance is created in the owner-side constructor.
 */
trait DeferredEventTrait
{
    /**
     * An associated deferred object for non-blocking dispatch
     *
     * @var Deferred|null
     */
    private ?Deferred $_deferred;

    /**
     * {@inheritDoc}
     */
    public function getPromise(): ?PromiseInterface
    {
        if (!$this->_deferred instanceof Deferred) {
            return null;
        }

        $promise = $this->_deferred->promise();

        return $promise;
    }

    /**
     * {@inheritDoc}
     */
    public function takeDeferred(): ?Deferred
    {
        $deferredTaken = $this->_deferred;

        $this->_deferred = null;

        return $deferredTaken;
    }
}
