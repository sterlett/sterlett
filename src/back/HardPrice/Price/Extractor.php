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

namespace Sterlett\HardPrice\Price;

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\HardPrice\Authentication;
use Sterlett\HardPrice\Authenticator\GuestAuthenticator;
use Sterlett\HardPrice\Item\ReadableStorageInterface as ItemStorageInterface;
use Sterlett\HardPrice\Price\Requester as PriceRequester;
use Throwable;

class Extractor
{
    /**
     * @var ItemStorageInterface
     */
    private ItemStorageInterface $itemStorage;

    /**
     * Performs authentication for the subsequent requests to mimic guest activity
     *
     * @var GuestAuthenticator
     */
    private GuestAuthenticator $requestAuthenticator;

    /**
     * Sends price data fetching requests to the HardPrice endpoint
     *
     * @var PriceRequester
     */
    private PriceRequester $priceRequester;

    /**
     * Extractor constructor.
     *
     * @param ItemStorageInterface $itemStorage
     * @param GuestAuthenticator   $requestAuthenticator Performs authentication for the subsequent requests
     * @param PriceRequester       $priceRequester       Sends price data fetching requests
     */
    public function __construct(
        ItemStorageInterface $itemStorage,
        GuestAuthenticator $requestAuthenticator,
        Requester $priceRequester
    ) {
        $this->itemStorage          = $itemStorage;
        $this->requestAuthenticator = $requestAuthenticator;
        $this->priceRequester       = $priceRequester;
    }

    public function extractPrice(int $hardwareIdentifier): PromiseInterface
    {
        $extractingDeferred = new Deferred();

        $hardwareItem = $this->itemStorage->require($hardwareIdentifier);

        $authenticationUriPath = $hardwareItem->getPageUri();
        $authenticationPromise = $this->requestAuthenticator->authenticate($authenticationUriPath);

        $authenticationPromise->then(
            function (Authentication $authentication) use ($extractingDeferred, $hardwareIdentifier) {
                try {
                    $responsePromise = $this->priceRequester->requestPrice($hardwareIdentifier, $authentication);

                    $extractingDeferred->resolve($responsePromise);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to request hardware prices.', 0, $exception);

                    $extractingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($extractingDeferred) {
                $reason = new RuntimeException('Unable to authenticate price fetching request.', 0, $rejectionReason);

                $extractingDeferred->reject($reason);
            }
        );

        $responsePromise = $extractingDeferred->promise();

        return $responsePromise;
    }
}
