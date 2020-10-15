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

namespace Sterlett\Bridge\React\Http\Response\Middleware;

use Psr\Http\Message\ResponseInterface;
use React\Http\Message\ResponseException;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use RuntimeException;
use Sterlett\Bridge\React\Http\Response\Middleware\BuffererMiddleware\ChunkBag;
use Sterlett\Bridge\React\Http\Response\MiddlewareInterface as ResponseMiddlewareInterface;
use Throwable;
use function RingCentral\Psr7\stream_for;

/**
 * Collects response body by small data chunks.
 *
 * Note: this middleware will block promise resolving chain (i.e. other middleware actions) until its work is done;
 * ensure this middleware will be called as the last one in the middleware chain.
 */
class BuffererMiddleware implements ResponseMiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function pass(PromiseInterface $responsePromise): PromiseInterface
    {
        $responseBufferedPromise = $this->bufferize($responsePromise);

        return $responseBufferedPromise;
    }

    /**
     * Handles a single body chunk for the response
     *
     * @param ChunkBag $bodyChunkBag Holds response body chunks
     * @param string   $bodyChunk    Received body chunk
     *
     * @return void
     */
    protected function onBodyChunk(ChunkBag $bodyChunkBag, string $bodyChunk): void
    {
        $bodyChunkBag->addChunk($bodyChunk);
    }

    /**
     * Returns a PSR-7 response message with buffered body
     *
     * @param ResponseInterface $response     Response message
     * @param ChunkBag          $bodyChunkBag Accumulated chunks for the response
     *
     * @return ResponseInterface
     */
    protected function onComplete(ResponseInterface $response, ChunkBag $bodyChunkBag): ResponseInterface
    {
        $bodyContents = '';
        $bodyChunks   = $bodyChunkBag->getChunks();

        foreach ($bodyChunks as $bodyChunk) {
            $bodyContents .= $bodyChunk;
        }

        $responseBody     = stream_for($bodyContents);
        $responseBuffered = $response->withBody($responseBody);

        return $responseBuffered;
    }

    /**
     * Returns a promise that will be resolved to a PSR-7 response message with buffered body
     *
     * @param PromiseInterface<ResponseInterface> $responsePromise Promise of response processing
     *
     * @return PromiseInterface<ResponseInterface>
     */
    private function bufferize(PromiseInterface $responsePromise): PromiseInterface
    {
        $bufferingDeferred = new Deferred();

        $responsePromise->then(
            function (ResponseInterface $response) use ($bufferingDeferred) {
                try {
                    $this->onResponseSuccess($bufferingDeferred, $response);
                } catch (Throwable $exception) {
                    $reason = new RuntimeException('Unable to configure response buffering.', 0, $exception);

                    $bufferingDeferred->reject($reason);
                }
            },
            function (Throwable $rejectionReason) use ($bufferingDeferred) {
                $reason = new RuntimeException('Unable to buffer response.', 0, $rejectionReason);

                $bufferingDeferred->reject($reason);
            }
        );

        $responseBufferedPromise = $bufferingDeferred->promise();

        return $responseBufferedPromise;
    }

    /**
     * Subscribes on events from the readable stream, representing response body, to collect all body chunks
     *
     * @param Deferred          $bufferingDeferred Represents the buffering process itself, used to control execution
     *                                             flow and promise handling
     * @param ResponseInterface $response          PSR-7 response message with readable stream
     *
     * @return void
     */
    private function onResponseSuccess(Deferred $bufferingDeferred, ResponseInterface $response)
    {
        /** @var ReadableStreamInterface $responseBody */
        $responseBody = $response->getBody();

        $bodyChunkBag = new ChunkBag();

        $responseBody->on(
            'data',
            function (string $bodyChunk) use ($bodyChunkBag) {
                $this->onBodyChunk($bodyChunkBag, $bodyChunk);
            }
        );

        $responseBody->on(
            'error',
            function (Throwable $rejectionReason) use ($bufferingDeferred) {
                $reason = new RuntimeException(
                    'Unable to buffer response (streaming error).',
                    0,
                    $rejectionReason
                );

                $bufferingDeferred->reject($reason);
            }
        );

        $responseBody->on(
            'close',
            function () use ($bufferingDeferred, $response, $bodyChunkBag) {
                $responseBuffered = $this->onComplete($response, $bodyChunkBag);

                $bufferingDeferred->resolve($responseBuffered);
            }
        );
    }
}
