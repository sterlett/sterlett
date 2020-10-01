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

namespace Sterlett\Hardware\Benchmark\Provider;

use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Sterlett\ClientInterface;
use Sterlett\Hardware\Benchmark\ProviderInterface;

/**
 * Obtains a list with hardware benchmark results from the PassMark website
 *
 * @see https://www.passmark.com
 */
class PassMarkProvider implements ProviderInterface
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
     * PassMarkProvider constructor.
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
     * {@inheritDoc}
     */
    public function getBenchmarks(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        $responsePromise = $this->httpClient->request('GET', $this->downloadUri);

        $responsePromise->then(
            function (ResponseInterface $response) use ($retrievingDeferred) {
                // todo: dom parsing (keeping low memory footprint)

                $retrievingDeferred->resolve([]);
            }
        );

        $retrievingPromise = $retrievingDeferred->promise();

        return $retrievingPromise;
    }
}
