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
use RuntimeException;
use Sterlett\ClientInterface;
use Sterlett\Hardware\Benchmark\ParserInterface;
use Sterlett\Hardware\Benchmark\ProviderInterface;
use Sterlett\Hardware\BenchmarkInterface;
use Throwable;
use Traversable;

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
     * Transforms raw benchmark data from the external resource to the list of application-level DTOs
     *
     * @var ParserInterface
     */
    private ParserInterface $benchmarkParser;

    /**
     * Resource identifier for data extracting
     *
     * @var string
     */
    private string $downloadUri;

    /**
     * PassMarkProvider constructor.
     *
     * @param ClientInterface $httpClient      Requests data from the external source
     * @param ParserInterface $benchmarkParser Transforms raw benchmark data from the external resource to the list of
     *                                         application-level DTOs
     * @param string          $downloadUri     Resource identifier for data extracting
     */
    public function __construct(ClientInterface $httpClient, ParserInterface $benchmarkParser, string $downloadUri)
    {
        $this->httpClient      = $httpClient;
        $this->benchmarkParser = $benchmarkParser;
        $this->downloadUri     = $downloadUri;
    }

    /**
     * {@inheritDoc}
     */
    public function getBenchmarks(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        $responsePromise = $this->httpClient->request('GET', $this->downloadUri);

        // it is possible to return a direct promise, based on retval of this call (using resolution forwarding or
        // "pipelining"), but it will make code less intuitive and predictable (because it leads to mixed value types
        // in the signatures of resolving callbacks from the chain), so a separate deferred is used here instead.
        $responsePromise->then(
            function (ResponseInterface $response) use ($retrievingDeferred) {
                try {
                    $benchmarks = $this->onResponse($response);

                    $retrievingDeferred->resolve($benchmarks);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to retrieve benchmarks (deserialization).', 0, $exception);

                    $retrievingDeferred->reject($reason);
                }
            },
            // handling HTTP request rejection.
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve benchmarks (request).', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $benchmarkListPromise = $retrievingDeferred->promise();

        return $benchmarkListPromise;
    }

    /**
     * Returns an iterable list with benchmarks (or a generator), configured by the given response instance
     *
     * @param ResponseInterface $response PSR-7 response message with benchmark list in the payload
     *
     * @return Traversable<BenchmarkInterface>|BenchmarkInterface[]
     */
    private function onResponse(ResponseInterface $response): iterable
    {
        $bodyAsString = (string) $response->getBody();

        $benchmarks = $this->benchmarkParser->parse($bodyAsString);

        return $benchmarks;
    }
}
