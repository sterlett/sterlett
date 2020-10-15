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

namespace Sterlett\Hardware\Price\Provider;

use Exception;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Hardware\Price\Provider\HardPrice\Authentication;
use Sterlett\Hardware\Price\Provider\HardPrice\Authenticator\GuestAuthenticator;
use Sterlett\Hardware\Price\Provider\HardPrice\IdExtractor;
use Sterlett\Hardware\Price\Provider\HardPrice\PriceRequester;
use Sterlett\Hardware\Price\Provider\HardPrice\PriceResponseReducer;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;
use Traversable;
use function React\Promise\all;

/**
 * Obtains a list with hardware prices from the HardPrice website
 *
 * @see https://hardprice.ru
 */
class HardPriceProvider implements ProviderInterface
{
    private IdExtractor $idExtractor;

    private GuestAuthenticator $requestAuthenticator;

    private PriceRequester $priceRequester;

    private PriceResponseReducer $responseReducer;

    public function __construct(
        IdExtractor $idExtractor,
        GuestAuthenticator $requestAuthenticator,
        PriceRequester $priceRequester,
        PriceResponseReducer $responseReducer
    ) {
        $this->idExtractor          = $idExtractor;
        $this->requestAuthenticator = $requestAuthenticator;
        $this->priceRequester       = $priceRequester;
        $this->responseReducer      = $responseReducer;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        $idListPromise = $this->idExtractor->getIdentifiers();

        // sending authorization request (for subsequent data queries) while we still waiting for the list of available
        // hardware identifiers.
        $authenticationPromise = $this->requestAuthenticator->authenticate();

        $idListAndAuthentication = all([$idListPromise, $authenticationPromise]);

        $idListAndAuthentication->then(
            function (array $idListAndAuthenticationResolved) use ($retrievingDeferred) {
                try {
                    /** @var Traversable<int>|int[] $hardwareIdentifiers */
                    /** @var Authentication $authentication */
                    [$hardwareIdentifiers, $authentication] = $idListAndAuthenticationResolved;

                    // querying data when the both authentication and identifiers are ready.
                    $priceListRequestedPromise = $this->onReady($hardwareIdentifiers, $authentication);

                    // transferring responsibility (resolver) from the retrieving process to the requesting process.
                    // we are closing the promise resolving chain at this point.
                    $retrievingDeferred->resolve($priceListRequestedPromise);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to retrieve prices (requests).', 0, $exception);

                    $retrievingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices (ids, auth).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $priceListPromise = $retrievingDeferred->promise();

        return $priceListPromise;
    }

    /**
     * Sending requests and collecting responses using MapReduce pattern
     *
     * @param Traversable<int>|int[] $hardwareIdentifiers
     * @param Authentication         $authentication
     *
     * @return PromiseInterface
     */
    private function onReady(iterable $hardwareIdentifiers, Authentication $authentication): PromiseInterface
    {
        $requestingDeferred = new Deferred();

        $this->priceRequester->setAuthentication($authentication);

        // map stage: acquiring a request promise for each hardware identifier and applying map function, to collect all
        // related data for the given identifiers at the reduce stage.
        $promisesMapped = [];

        foreach ($hardwareIdentifiers as $hardwareIdentifier) {
            $responsePromise = $this->priceRequester->requestPrice($hardwareIdentifier);

            // map function: list(promise, id) -> list(response, id).
            $promiseMapped = $responsePromise->then(
                function (ResponseInterface $response) use ($hardwareIdentifier) {
                    return [$response, $hardwareIdentifier];
                },
                function (Throwable $rejectionReason) {
                    throw new RuntimeException('Unable to apply map function to the response.', 0, $rejectionReason);
                }
            );

            $promisesMapped[] = $promiseMapped;
        }

        // reduce stage: collecting all responses and aggregating them into a single data structure for centralized
        // processing with the "onFulfilled" callbacks.
        $reducePromise = $this->responseReducer->reduce($promisesMapped);

        $reducePromise->then(
            function (iterable $responseListById) use ($requestingDeferred) {
                foreach ($responseListById as $hardwareIdentifier => $responseList) {
                    // todo: apply sorting behavior (from the most expensive to the cheapest ones)
                }

                // todo: parse raw data into price DTOs (+ try-catch)

                $requestingDeferred->resolve([]);
            },
            function (Exception $rejectionReason) use ($requestingDeferred) {
                $reason = new RuntimeException('Unable to reduce price responses.', 0, $rejectionReason);

                $requestingDeferred->reject($reason);
            }
        );

        $priceListPromise = $requestingDeferred->promise();

        return $priceListPromise;
    }
}
