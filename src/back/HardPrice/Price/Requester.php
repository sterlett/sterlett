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

use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\ClientInterface;
use Sterlett\HardPrice\Authentication;
use Sterlett\HardPrice\ChromiumHeaders;

/**
 * Sends price data fetching requests to the HardPrice endpoint
 */
class Requester
{
    /**
     * Requests data from the external source
     *
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * Endpoint for price fetching requests
     *
     * @var string
     */
    private string $priceListUri;

    /**
     * Requester constructor.
     *
     * @param ClientInterface $httpClient   Requests data from the external source
     * @param string          $priceListUri Endpoint for price fetching requests
     */
    public function __construct(ClientInterface $httpClient, string $priceListUri)
    {
        $this->httpClient   = $httpClient;
        $this->priceListUri = $priceListUri;
    }

    /**
     * Returns a promise that will be resolved to the PSR-7 response message with price data for the given hardware
     * identifier
     *
     * @param int            $hardwareIdentifier Identifier of the item for which the request is being sent
     * @param Authentication $authentication     Holds authentication data payload for the request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function requestPrice(int $hardwareIdentifier, Authentication $authentication): PromiseInterface
    {
        $requestHeaders = [
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ];

        $sessionCookies           = $authentication->getCookies();
        $sessionCookieAggregated  = implode(';', $sessionCookies);

        if (empty($sessionCookieAggregated)) {
            throw new RuntimeException('Cookie must be specified for price fetching request.');
        }

        $requestHeaders['Cookie'] = $sessionCookieAggregated;

        $csrfToken = $authentication->getCsrfToken();

        if (empty($csrfToken)) {
            throw new RuntimeException('CSRF token must be specified for price fetching request.');
        }

        $requestHeaders['X-CSRF-TOKEN'] = $csrfToken;

        $requestPayload = ['id' => $hardwareIdentifier];
        $requestBody    = http_build_query($requestPayload);

        $requestHeaders['Content-Length'] = strlen($requestBody);

        $requestHeaders = ChromiumHeaders::makeFrom($requestHeaders);

        $responsePromise = $this->httpClient->request('POST', $this->priceListUri, $requestHeaders, $requestBody);

        return $responsePromise;
    }
}
