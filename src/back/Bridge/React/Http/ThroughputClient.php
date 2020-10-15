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
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
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
     * Options for the client
     *
     * @var array
     */
    private array $options;

    /**
     * Internal queue with delayed requests
     *
     * @var Queue
     */
    private Queue $_requestsPending;

    /**
     * An internal counter, that is used to maintain configured throughput
     *
     * @var int
     */
    private int $_concurrentRequests;

    /**
     * ThroughputClient constructor.
     *
     * @param ClientInterface $httpClient A base client implementation that performs actual requests
     * @param LoopInterface   $loop       Event loop that is used by the base client implementation
     * @param array           $options    Options for the client
     */
    public function __construct(ClientInterface $httpClient, LoopInterface $loop, array $options)
    {
        $this->httpClient = $httpClient;
        $this->loop       = $loop;

        $optionsResolver = new OptionsResolver();

        $optionsResolver
            ->define('requests_per_second')
            ->info('The limit for all outgoing requests, per second')
            ->allowedTypes('int', 'float')
            ->default(1.0)
            ->normalize(
                function (Options $options, $requestsPerSecond) {
                    $requestsPerSecondNormalized = (float) max(0.1, $requestsPerSecond);

                    return $requestsPerSecondNormalized;
                }
            )
        ;

        $optionsResolver
            ->define('concurrent_requests')
            ->info('Max count of pending requests at the same unit of time')
            ->allowedTypes('int')
            ->default(1)
            ->normalize(
                function (Options $options, int $concurrentRequests) {
                    $concurrentRequestsNormalized = max(1, $concurrentRequests);

                    return $concurrentRequestsNormalized;
                }
            )
        ;

        $this->options = $optionsResolver->resolve($options);

        $this->_requestsPending    = new Queue();
        $this->_concurrentRequests = 0;

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
        // calculated delay for all outgoing requests (based on the given RPS count).
        $sendingDelayInSeconds = round(1 / $this->options['requests_per_second'], 3);

        $this->loop->addPeriodicTimer(
            $sendingDelayInSeconds,
            function () {
                if ($this->_requestsPending->isEmpty()) {
                    return;
                }

                if ($this->_concurrentRequests >= $this->options['concurrent_requests']) {
                    return;
                }

                /** @var Deferred $requestingDeferred */
                [$requestContext, $requestingDeferred] = $this->_requestsPending->pop();

                try {
                    [$method, $url, $headers, $body] = $requestContext;

                    $responsePromise = $this->httpClient->request($method, $url, $headers, $body);

                    ++$this->_concurrentRequests;

                    $responsePromise->then(
                        function () {
                            --$this->_concurrentRequests;
                        },
                        function () {
                            --$this->_concurrentRequests;
                        }
                    );

                    $requestingDeferred->resolve($responsePromise);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to send a delayed request.', 0, $exception);

                    $requestingDeferred->reject($reason);
                }
            }
        );
    }
}