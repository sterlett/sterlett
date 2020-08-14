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

namespace Sterlett\Bridge\Symfony\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\ServiceReferenceGraph;
use Traversable;

/**
 * Used to forcefully instantiate services from the DI container that don't have any active parent connections in the
 * dependency graph, e.g. self-sustained ReactPHP streams, which grabs some data from the provided resource and sends it
 * as an event for subscribers.
 *
 * @see ServiceReferenceGraph
 */
class ServiceWarmer
{
    /**
     * Tag to request service from the DI container for warmup
     *
     * @var string
     */
    public const WARMABLE_TAG = 'service_warmer.warmable';

    /**
     * ServiceWarmer constructor.
     *
     * @param Traversable $coldOnes Services from the DI container that should be forcefully created
     */
    public function __construct(Traversable $coldOnes)
    {
        $this->warmup($coldOnes);
    }

    private function warmup(Traversable $coldOnes): void
    {
        foreach ($coldOnes as $coldOne) {
            if (!$coldOne instanceof Traversable) {
                continue;
            }

            $this->warmup($coldOne);
        }
    }
}
