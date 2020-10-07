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
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\ClientInterface;
use Throwable;

/**
 * Extracts a list with available hardware identifiers for data queries (external ids)
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
     * @param IdParser        $idParser
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
     * Resolves into Traversable<int> or int[].
     *
     * @return PromiseInterface<iterable>
     */
    public function getIdentifiers(): PromiseInterface
    {
        $extractingDeferred = new Deferred();

        $responsePromise = $this->httpClient->request('GET', $this->downloadUri);

        $responsePromise->then(
            function (ResponseInterface $response) use ($extractingDeferred) {
                $bodyAsString        = (string) $response->getBody();
                $hardwareIdentifiers = $this->idParser->parse($bodyAsString);

                $extractingDeferred->resolve($hardwareIdentifiers);
            },
            function (Throwable $rejectionReason) use ($extractingDeferred) {
                $reason = new RuntimeException('Unable to extract external identifiers.', 0, $rejectionReason);

                $extractingDeferred->reject($reason);
            }
        );

        $idListPromise = $extractingDeferred->promise();

        return $idListPromise;
    }
}
