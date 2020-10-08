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
use Sterlett\Dto\Hardware\Price;
use Sterlett\Hardware\Price\Provider\HardPrice\Authentication;
use Sterlett\Hardware\Price\Provider\HardPrice\Authenticator\GuestAuthenticator;
use Sterlett\Hardware\Price\Provider\HardPrice\IdExtractor;
use Sterlett\Hardware\Price\Provider\HardPrice\PriceRequester;
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

    public function __construct(
        IdExtractor $idExtractor,
        GuestAuthenticator $requestAuthenticator,
        PriceRequester $priceRequester
    ) {
        $this->idExtractor          = $idExtractor;
        $this->requestAuthenticator = $requestAuthenticator;
        $this->priceRequester       = $priceRequester;
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
                /** @var Traversable<int>|int[] $hardwareIdentifiers */
                /** @var Authentication $authentication */
                [$hardwareIdentifiers, $authentication] = $idListAndAuthenticationResolved;

                // querying data when the both authentication and identifiers are ready.
                $priceListRequestedPromise = $this->onReady($hardwareIdentifiers, $authentication);

                // transferring responsibility (resolver) from the retrieving process to the requesting process.
                // we are closing the promise resolving chain at this point.
                $retrievingDeferred->resolve($priceListRequestedPromise);
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices.', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $priceListPromise = $retrievingDeferred->promise();

        return $priceListPromise;
    }

    private function onReady(iterable $hardwareIdentifiers, Authentication $authentication): PromiseInterface
    {
        $requestingDeferred = new Deferred();

        $this->priceRequester->setAuthentication($authentication);

        $requestPromise = $this->priceRequester->requestPrices($hardwareIdentifiers);

        $requestPromise->then(
            function (ResponseInterface $response) use ($requestingDeferred) {
                // todo: parse raw data into price DTOs

                $price = new Price();
                $price->setHardwareName('Test');
                $price->setSellerIdentifier('seller1');
                $price->setAmount(10);
                $price->setPrecision(4);
                $price->setCurrency('RUR');

                $prices = [$price];

                $requestingDeferred->resolve($prices);
            },
            function (Exception $rejectionReason) use ($requestingDeferred) {
                $reason = new RuntimeException('Unable to request prices.', 0, $rejectionReason);

                $requestingDeferred->reject($reason);
            }
        );

        $priceListPromise = $requestingDeferred->promise();

        return $priceListPromise;
    }
}
