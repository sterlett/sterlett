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

use React\Promise\PromiseInterface;
use Sterlett\ClientInterface;

class PriceRequester
{
    private ClientInterface $httpClient;

    private ?Authentication $authentication;

    private string $priceListUri;

    public function __construct(ClientInterface $httpClient, string $priceListUri)
    {
        $this->httpClient = $httpClient;

        $this->authentication = null;
        $this->priceListUri   = $priceListUri;
    }

    public function requestPrice(int $hardwareIdentifier): PromiseInterface
    {
        $requestHeaders = [
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ];

        $sessionCookies           = $this->authentication->getCookies();
        $sessionCookieAggregated  = implode(';', $sessionCookies);
        $requestHeaders['cookie'] = $sessionCookieAggregated;

        $csrfToken                      = $this->authentication->getCsrfToken();
        $requestHeaders['x-csrf-token'] = $csrfToken;

        $requestPayload = ['id' => $hardwareIdentifier];
        $requestBody    = http_build_query($requestPayload);

        $responsePromise = $this->httpClient->request('POST', $this->priceListUri, $requestHeaders, $requestBody);

        return $responsePromise;
    }

    public function setAuthentication(Authentication $authentication): void
    {
        $this->authentication = $authentication;
    }
}
