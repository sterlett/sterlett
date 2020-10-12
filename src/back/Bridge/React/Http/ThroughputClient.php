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

use Ds\Queue;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\ClientInterface;
use Throwable;

/**
 * Delays all outgoing requests to maintain the configured RPS (requests per second) count for the client instance
 */
class ThroughputClient implements ClientInterface
{
    /**
     * A base client implementation that performs actual requests
     *
     * @var ClientInterface
     */
    private ClientInterface $httpClient;

    /**
     * Event loop that is used by the base client implementation
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * The limit for all outgoing requests, per second
     *
     * @var int
     */
    private int $requestPerSecondCount;

    /**
     * A queue with requests for sending
     *
     * @var Queue
     */
    private Queue $_requestsPending;

    /**
     * ThroughputClient constructor.
     *
     * @param ClientInterface $httpClient            A base client implementation that performs actual requests
     * @param LoopInterface   $loop                  Event loop that is used by the base client implementation
     * @param int             $requestPerSecondCount The limit for all outgoing requests, per second
     */
    public function __construct(ClientInterface $httpClient, LoopInterface $loop, int $requestPerSecondCount)
    {
        $this->httpClient            = $httpClient;
        $this->loop                  = $loop;
        $this->requestPerSecondCount = $requestPerSecondCount;

        $this->_requestsPending = new Queue();

        $this->registerPeriodicTimer();
    }

    /**
     * {@inheritDoc}
     */
    public function request($method, $url, iterable $headers = [], $body = ''): PromiseInterface
    {
        $requestingDeferred = new Deferred();
        $requestContext     = [$method, $url, $headers, $body];

        $this->_requestsPending->push([$requestContext, $requestingDeferred]);

        $responsePromise = $requestingDeferred->promise();

        return $responsePromise;
    }

    /**
     * Adds a periodic timer for the event loop that sends requests one by one, maintaining configured throughput
     *
     * @return void
     */
    private function registerPeriodicTimer(): void
    {
        $rpsNormalized = max(1, $this->requestPerSecondCount);

        // calculated delay for all outgoing requests (based on the given RPS count).
        $sendingDelayInSeconds = round(1 / $rpsNormalized, 3);

        $this->loop->addPeriodicTimer(
            $sendingDelayInSeconds,
            function () {
                if ($this->_requestsPending->isEmpty()) {
                    return;
                }

                /** @var Deferred $requestingDeferred */
                [$requestContext, $requestingDeferred] = $this->_requestsPending->pop();

                try {
                    [$method, $url, $headers, $body] = $requestContext;

                    $responsePromise = $this->httpClient->request($method, $url, $headers, $body);

                    $requestingDeferred->resolve($responsePromise);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to send a delayed request.', 0, $exception);

                    $requestingDeferred->reject($reason);
                }
            }
        );
    }
}
