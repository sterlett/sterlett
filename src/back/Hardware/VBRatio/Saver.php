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
 * Saves V/B ratio data in the local storage
 */
class Saver
{
    /**
     * A storage with V/B ratio records for the hardware items
     *
     * @var Repository
     */
    private Repository $ratioRepository;

    /**
     * Saver constructor.
     *
     * @param Repository $ratioRepository A storage with V/B ratio records for the hardware items
     */
    public function __construct(Repository $ratioRepository)
    {
        $this->ratioRepository = $ratioRepository;
    }

    /**
     * Saves V/B ratio records in the local storage using a repository service
     *
     * @param iterable $ratios V/B ratio records
     *
     * @return PromiseInterface<null>
     */
    public function saveRatios(iterable $ratios): PromiseInterface
    {
        // todo: forward promises (promise all)
        foreach ($ratios as $ratio) {
            $this->ratioRepository->save($ratio);
        }

        return resolve(null);
    }
}
