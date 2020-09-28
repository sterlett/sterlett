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
use Sterlett\ClientInterface;
use Traversable;

/**
 * Adapter for Browser component from the ReactPHP package that forces it to stream body of HTTP responses by small
 * chunks of data for the subscribed observers, i.e. without body buffering
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
     * Client constructor.
     *
     * @param Browser $browser Sends HTTP requests and keeps track of pending incoming HTTP responses
     */
    public function __construct(Browser $browser)
    {
        $this->browser = $browser;
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

        return $this->browser->requestStreaming($method, $url, $headerArray, $body);
    }
}
