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

use React\Promise\PromiseInterface;
use Sterlett\ClientInterface;
use Sterlett\Hardware\Benchmark\ProviderInterface;
use function React\Promise\resolve;

/**
 * Obtains a list with hardware benchmarks results from the PassMark website
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
    private string $dataUri;

    /**
     * PassMarkProvider constructor.
     *
     * @param ClientInterface $httpClient Requests data from the external source
     * @param string          $dataUri    Resource identifier for data extracting
     */
    public function __construct(ClientInterface $httpClient, string $dataUri)
    {
        $this->httpClient = $httpClient;
        $this->dataUri    = $dataUri;
    }

    /**
     * {@inheritDoc}
     */
    public function getBenchmarks(): PromiseInterface
    {
        // todo: resource extracting & dom parsing (low memory footprint)

        return resolve([]);
    }
}
