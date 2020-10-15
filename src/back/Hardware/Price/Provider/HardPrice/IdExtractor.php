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

use Psr\Http\Message\ResponseInterface;
use React\Http\Message\ResponseException;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\ClientInterface;
use Throwable;
use Traversable;

/**
 * Extracts a list with available hardware identifiers for data queries to the HardPrice website
 */
class IdExtractor
{
    /**
     * Requests data from the external source
     *
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * Transforms external identifiers from the raw format to the iterable list of normalized values
     *
     * @var IdParser
     */
    private IdParser $idParser;

    /**
     * Resource identifier for data extracting
     *
     * @var string
     */
    private string $downloadUri;

    /**
     * IdExtractor constructor.
     *
     * @param ClientInterface $httpClient  Requests data from the external source
     * @param IdParser        $idParser    Transforms external identifiers from the raw format to the iterable list of
     *                                     normalized values
     * @param string          $downloadUri Resource identifier for data extracting
     */
    public function __construct(ClientInterface $httpClient, IdParser $idParser, string $downloadUri)
    {
        $this->httpClient  = $httpClient;
        $this->idParser    = $idParser;
        $this->downloadUri = $downloadUri;
    }

    /**
     * Returns an iterable list with available hardware identifiers.
     *
     * Resolves to an instance of Traversable<int> or int[].
     *
     * @return PromiseInterface<iterable>
     */
    public function getIdentifiers(): PromiseInterface
    {
        $extractingDeferred = new Deferred();

        $requestHeaders  = ChromiumHeaders::makeFrom([]);
        $responsePromise = $this->httpClient->request('GET', $this->downloadUri, $requestHeaders);

        $responsePromise->then(
            function (ResponseInterface $response) use ($extractingDeferred) {
                try {
                    $hardwareIdentifiers = $this->onResponseSuccess($response);

                    $extractingDeferred->resolve($hardwareIdentifiers);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException(
                        'Unable to extract hardware identifiers (deserialization).',
                        0,
                        $exception
                    );

                    $extractingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($extractingDeferred) {
                $reason = new RuntimeException(
                    'Unable to extract hardware identifiers (request).',
                    0,
                    $rejectionReason
                );

                $extractingDeferred->reject($reason);
            }
        );

        $idListPromise = $extractingDeferred->promise();

        return $idListPromise;
    }

    /**
     * Returns a list with hardware identifiers (or a generator)
     *
     * @param ResponseInterface $response PSR-7 response message with hardware identifiers payload
     *
     * @return Traversable<int>|int[]
     */
    private function onResponseSuccess(ResponseInterface $response): iterable
    {
        $bodyAsString = (string) $response->getBody();

        $hardwareIdentifiers = $this->idParser->parse($bodyAsString);

        return $hardwareIdentifiers;
    }
}
