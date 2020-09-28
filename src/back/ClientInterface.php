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

namespace Sterlett;

use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Requests data from the external source
 */
interface ClientInterface
{
    /**
     * Performs HTTP request and returns a promise that resolves into a PSR-7 response message (async approach by
     * default). Adapters for environments with blocking I/O should return a fulfilled promise.
     *
     * This method shouldn't throw exceptions, all errors must be handled using rejection callbacks.
     *
     * @param mixed    $method  Method for HTTP request
     * @param mixed    $url     Request URI
     * @param iterable $headers An iterable list of request headers
     * @param mixed    $body    Request body
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function request($method, $url, iterable $headers = [], $body = ''): PromiseInterface;
}
