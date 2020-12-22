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

use Iterator;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\ClientInterface;
use Sterlett\Dto\Hardware\Price;
use Sterlett\HardPrice\ChromiumHeaders;
use Sterlett\HardPrice\Price\FallbackParser as PriceParser;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;

/**
 * A reserve provider for fetching prices from the HardPrice website (gen 1).
 *
 * Gives a lesser price list (only the most popular/easy to parse stores), but works much faster than the main one.
 *
 * Designed for the console interface. Algorithms that are based on this provider will yield, as a consequence, less
 * accurate data, but will return a result almost immediately (prices are still relevant).
 *
 * @see ScrapingProvider
 * @see BrowsingProvider
 */
class FallbackProvider implements ProviderInterface
{
    /**
     * Sends a price fetching request to the fallback endpoint
     *
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * Yields application-level DTOs while processing raw data from the fallback endpoint (single response)
     *
     * @var PriceParser
     */
    private PriceParser $priceParser;

    /**
     * The fallback endpoint for price fetching request
     *
     * @var string
     */
    private string $priceListUri;

    /**
     * FallbackProvider constructor.
     *
     * @param ClientInterface $httpClient   Sends a price fetching request to the fallback endpoint
     * @param PriceParser     $priceParser  Yields application-level DTOs while processing raw data
     * @param string          $priceListUri The fallback endpoint for price fetching request
     */
    public function __construct(ClientInterface $httpClient, PriceParser $priceParser, string $priceListUri)
    {
        $this->httpClient   = $httpClient;
        $this->priceParser  = $priceParser;
        $this->priceListUri = $priceListUri;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        $requestHeaders  = ChromiumHeaders::makeFrom([]);
        $responsePromise = $this->httpClient->request('GET', $this->priceListUri, $requestHeaders);

        $responsePromise->then(
            function (ResponseInterface $response) use ($retrievingDeferred) {
                try {
                    $priceIterator = $this->onResponseSuccess($response);

                    $retrievingDeferred->resolve($priceIterator);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException(
                        'Unable to retrieve hardware prices (deserialization).',
                        0,
                        $exception
                    );

                    $retrievingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to send a price fetching request.', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $priceListPromise = $retrievingDeferred->promise();

        return $priceListPromise;
    }

    /**
     * Performs hardware price parsing and returns an iterator for collection traversing
     *
     * @param ResponseInterface $response PSR-7 response message with price data for all available items
     *
     * @return Iterator<Price>
     */
    private function onResponseSuccess(ResponseInterface $response): Iterator
    {
        $bodyAsString = (string) $response->getBody();

        $priceIterator = $this->priceParser->parse($bodyAsString);

        return $priceIterator;
    }
}
