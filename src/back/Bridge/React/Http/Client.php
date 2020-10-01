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

namespace Sterlett\Bridge\React\Http;

use React\Http\Browser;
use React\Promise\PromiseInterface;
use Sterlett\Bridge\React\Http\Response\MiddlewareInterface as ResponseMiddlewareInterface;
use Sterlett\ClientInterface;
use Traversable;

/**
 * Adapter for Browser component from the ReactPHP package that forces it to stream body of HTTP responses by small
 * chunks of data for the subscribed observers, i.e. without body prebuffering by default.
 *
 * The actual strategy for response buffering is delegated to the middleware, i.e. client can be configured both in
 * streaming and non-streaming mode. Middleware can also be used to track progress of chunked transfer and other
 * data analysis.
 *
 * @see ResponseMiddlewareInterface
 */
class Client implements ClientInterface
{
    /**
     * Sends HTTP requests and keeps track of pending HTTP responses
     *
     * @var Browser
     */
    private Browser $browser;

    /**
     * List of middleware for additional response processing (e.g. buffering, progress tracking, etc.)
     *
     * @var Traversable<ResponseMiddlewareInterface>|ResponseMiddlewareInterface[]
     */
    private iterable $responseMiddlewareList;

    /**
     * Client constructor.
     *
     * @param Browser  $browser                Sends HTTP requests and keeps track of pending HTTP responses
     * @param iterable $responseMiddlewareList List of middleware for additional response processing
     */
    public function __construct(Browser $browser, iterable $responseMiddlewareList)
    {
        $this->browser                = $browser;
        $this->responseMiddlewareList = $responseMiddlewareList;
    }

    /**
     * {@inheritDoc}
     */
    public function request($method, $url, iterable $headers = [], $body = ''): PromiseInterface
    {
        if ($headers instanceof Traversable) {
            $headerArray = iterator_to_array($headers);
        } else {
            $headerArray = (array) $headers;
        }

        $responsePromise = $this->browser->requestStreaming($method, $url, $headerArray, $body);

        // all individual response body chunks will be collected one by one manually, with middleware, to control and
        // visualize the whole request-response workflow progress, for the cases when we are downloading a large
        // resource (since "onProgress" callback from the React's Promise contract is marked as deprecated).
        foreach ($this->responseMiddlewareList as $responseMiddleware) {
            $responsePromise = $responseMiddleware->pass($responsePromise);
        }

        return $responsePromise;
    }
}
