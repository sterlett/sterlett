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

namespace Sterlett\Hardware\Price\Provider\HardPrice;

use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use Sterlett\Progress\TrackerInterface;
use Traversable;
use function React\Promise\reduce;

/**
 * Applies a reduce function to the list of response promises for collecting stage
 */
class PriceResponseReducer
{
    /**
     * Tracks progress of reduce stage for price responses
     *
     * @var TrackerInterface|null
     */
    private ?TrackerInterface $progressTracker;

    /**
     * PriceResponseReducer constructor.
     */
    public function __construct()
    {
        $this->progressTracker = null;
    }

    /**
     * Returns a promise that resolves to the data structure, representing a set of aggregated (by id) responses
     * with price data payload.
     *
     * An iterable list is expected in the form of Traversable wrapper for PromiseInterface<ResponseInterface>
     * or as an array with elements of the same type.
     *
     * @param iterable $responsePromises An iterable list of response promises from the requester
     *
     * @return PromiseInterface<array>
     */
    public function reduce(iterable $responsePromises): PromiseInterface
    {
        if ($responsePromises instanceof Traversable) {
            // this is a potentially blocking call, so there should not be any heavy logic (e.g. from the generators).
            $promiseArray = iterator_to_array($responsePromises);
        } else {
            $promiseArray = (array) $responsePromises;
        }

        // todo: move tracking logic to the mapper instead
        if ($this->progressTracker instanceof TrackerInterface) {
            $promiseCount = count($promiseArray);

            $this->progressTracker->setMaxSteps($promiseCount);
            $this->progressTracker->start();
        }

        $reducePromise = reduce(
            $promiseArray,
            // reduce function: list(id, list(response, id)) -> list(id, responses merged).
            function (array $responseListById, array $responseWithId, int $responseIndex, int $responseCountTotal) {
                $this->onResponseWithId($responseListById, $responseWithId);

                $this->onResponseIndex($responseIndex, $responseCountTotal);

                return $responseListById;
            },
            // initial value for the reduce container: $responseListById (responses merged).
            []
        );

        return $reducePromise;
    }

    /**
     * Sets progress tracker for reduce stage visualization
     *
     * @param TrackerInterface $progressTracker Tracks progress of reduce stage for price responses
     *
     * @return void
     */
    public function setProgressTracker(TrackerInterface $progressTracker): void
    {
        $this->progressTracker = $progressTracker;
    }

    public function onResponseWithId(array &$responseListById, array $responseWithId): void
    {
        /** @var ResponseInterface $response */
        /** @var int $hardwareIdentifier */
        [$response, $hardwareIdentifier] = $responseWithId;

        if (!array_key_exists($hardwareIdentifier, $responseListById)) {
            $responseListById[$hardwareIdentifier] = [$response];

            return;
        }

        $responseListById[$hardwareIdentifier][] = $response;
    }

    public function onResponseIndex(int $responseIndex, int $responseCountTotal): void
    {
        if (!$this->progressTracker instanceof TrackerInterface) {
            return;
        }

        $this->progressTracker->advance(1);

        $responseNumber = $responseIndex + 1;

        if ($responseNumber < $responseCountTotal) {
            return;
        }

        $this->progressTracker->finish();
    }
}
