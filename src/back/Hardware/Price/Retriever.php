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

namespace Sterlett\Hardware\Price;

use React\Promise\PromiseInterface;
use function React\Promise\resolve;

/**
 * Finds price records for the different hardware items and saves them into the local storage
 */
class Retriever
{
    /**
     * Encapsulates the price retrieving algorithm (async approach)
     *
     * @var ProviderInterface
     */
    private ProviderInterface $priceProvider;

    /**
     * A storage with price records
     *
     * @var Repository
     */
    private Repository $priceRepository;

    /**
     * Retriever constructor.
     *
     * @param ProviderInterface $priceProvider   Encapsulates the price retrieving algorithm (async approach)
     * @param Repository        $priceRepository A storage with price records
     */
    public function __construct(ProviderInterface $priceProvider, Repository $priceRepository)
    {
        $this->priceProvider   = $priceProvider;
        $this->priceRepository = $priceRepository;
    }

    /**
     * Extracts hardware prices from the specified provider and saves them using a repository reference. Returns a
     * promise that will be resolved when the price retrieving process is complete (or errored).
     *
     * @return PromiseInterface<null>
     */
    public function retrievePrices(): PromiseInterface
    {
        // todo

        return resolve(null);
    }
}
