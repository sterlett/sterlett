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

use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;
use Sterlett\ClientInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Traversable;
use function Clue\React\Block\await;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Adapter for Browser component from the ReactPHP package that sends an HTTP request and awaits a response, with
 * buffered body content; utilized as a fallback for environment with blocking I/O.
 *
 * Usage example:
 * ```
 * $responsePromise = $blockingClient->request('GET', 'https://www.php-fig.org');
 *
 * $response  = null;
 * $exception = null;
 *
 * $responsePromise->then(
 *     function (\Psr\Http\Message\ResponseInterface $psrResponse) use (&$response) {
 *         $response = $psrResponse;
 *     },
 *     function (Exception $rejectionReason) use (&$exception) {
 *         $exception = $rejectionReason;
 *     }
 * );
 *
 * // response (or exception) is available immediately here, like with other traditional synchronous clients.
 * if ($response instanceof \Psr\Http\Message\ResponseInterface) {
 *     $body = (string) $response->getBody();
 * } else {
 *     // handle exception.
 * }
 * ```
 */
class BlockingClient implements ClientInterface
{
    /**
     * Logs errors for response processing logic
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Sends HTTP requests and keeps track of pending HTTP responses
     *
     * @var Browser
     */
    private Browser $browser;

    /**
     * Event loop that is used by the given browser instance
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * Parameters for sending HTTP requests
     *
     * @var array
     */
    private array $options;

    /**
     * BlockingClient constructor.
     *
     * @param LoggerInterface $logger  Logs errors for response processing logic
     * @param Browser         $browser Sends HTTP requests and keeps track of pending incoming HTTP responses
     * @param LoopInterface   $loop    Event loop that is used by the given browser instance
     * @param array           $options Parameters for sending HTTP requests
     */
    public function __construct(LoggerInterface $logger, Browser $browser, LoopInterface $loop, array $options = [])
    {
        $this->logger  = $logger;
        $this->browser = $browser;
        $this->loop    = $loop;

        $optionsResolver = new OptionsResolver();

        $optionsResolver->define('timeout')
            ->default(5.0)
            ->allowedTypes('float')
            ->info('Maximum time for I/O blocking during request processing, until we meet the default socket timeout')
        ;

        $this->options = $optionsResolver->resolve($options);
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

        $responsePromise = $this->browser->request($method, $url, $headerArray, $body);

        try {
            $response         = await($responsePromise, $this->loop, $this->options['timeout']);
            $promiseFulfilled = resolve($response);

            return $promiseFulfilled;
        } catch (Exception $exception) {
            $exceptionCode    = $exception->getCode();
            $exceptionMessage = $exception->getMessage();

            $this->logger->error(
                'An error has been occurred while sending a request. ({exceptionCode}){exceptionMessage}',
                [
                    'exceptionCode'    => $exceptionCode,
                    'exceptionMessage' => $exceptionMessage,
                ]
            );

            $promiseRejected = reject($exception);

            return $promiseRejected;
        }
    }
}
