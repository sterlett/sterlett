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

namespace Sterlett\HardPrice\Response;

use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use Sterlett\Progress\TrackerInterface;
use function React\Promise\reduce;

/**
 * Applies a reduce function to the list of response promises for collecting stage
 */
class Reducer
{
    /**
     * Tracks progress of reduce stage for HTTP responses
     *
     * @var TrackerInterface|null
     */
    private ?TrackerInterface $progressTracker;

    /**
     * Reducer constructor.
     *
     * @param TrackerInterface|null $progressTracker Tracks progress of reduce stage for HTTP responses
     */
    public function __construct(TrackerInterface $progressTracker = null)
    {
        $this->progressTracker = $progressTracker;
    }

    /**
     * Returns a promise that resolves to the data structure, representing a set of aggregated (by id) HTTP responses.
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
        // this is a potentially blocking statement, so there should not be any heavy logic (e.g. from the generators).
        $promiseArray = [...$responsePromises];

        // todo: move tracking logic to the separate unit (at onReady level) instead (or make a TrackableReducer)
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
