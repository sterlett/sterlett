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
use function React\Promise\resolve;

/**
 * Updates bindings (price-benchmark) for the V/B ratio data
 */
class BindingsUpdater
{
    /**
     * A storage with V/B ratio records for the hardware items
     *
     * @var Repository
     */
    private Repository $ratioRepository;

    /**
     * BindingsSaver constructor.
     *
     * @param Repository $ratioRepository A storage with V/B ratio records for the hardware items
     */
    public function __construct(Repository $ratioRepository)
    {
        $this->ratioRepository = $ratioRepository;
    }

    /**
     * Saves bindings for the V/B ratio records in the local storage
     *
     * @param iterable $ratios V/B ratio records
     *
     * @return PromiseInterface<null>
     */
    public function updateBindings(iterable $ratios): PromiseInterface
    {
        $clearConfirmationPromise = $this->ratioRepository->removeBindings();

        $clearConfirmationPromise->then(
            function () use ($ratios) {
                // todo: forward promises (promise all)
                foreach ($ratios as $ratio) {
                    $this->ratioRepository->saveBindings($ratio);
                }
            },
            // todo: handle rejection
        );

        return resolve(null);
    }
}
