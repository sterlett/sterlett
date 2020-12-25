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

namespace Sterlett\HardPrice\Item;

use React\Promise\PromiseInterface;
use Sterlett\ClientInterface;
use Sterlett\HardPrice\ChromiumHeaders;

/**
 * Sends a request to get available hardware items from the HardPrice website
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
     * Resource identifier for data extracting
     *
     * @var string
     */
    private string $downloadUri;

    /**
     * Requester constructor.
     *
     * @param ClientInterface $httpClient  Requests data from the external source
     * @param string          $downloadUri Resource identifier for data extracting
     */
    public function __construct(ClientInterface $httpClient, string $downloadUri)
    {
        $this->httpClient  = $httpClient;
        $this->downloadUri = $downloadUri;
    }

    /**
     * Returns a promise that resolves to the instance of PSR-7 response message for hardware items extracting
     *
     * Resolves to an instance of Traversable<Item> or Item[].
     *
     * @return PromiseInterface<iterable>
     */
    public function requestItems(): PromiseInterface
    {
        $requestHeaders = ChromiumHeaders::makeFrom([]);

        $responsePromise = $this->httpClient->request('GET', $this->downloadUri, $requestHeaders);

        return $responsePromise;
    }
}
