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
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;
use RuntimeException;
use Sterlett\Bridge\React\Http\Response\MiddlewareInterface as ResponseMiddlewareInterface;
use Sterlett\Progress\TrackerInterface;
use Throwable;

/**
 * Tracks response handling process using the configured activity tracker
 */
class TrackerMiddleware implements ResponseMiddlewareInterface
{
    /**
     * Tracks activity progress using steps
     *
     * @var TrackerInterface
     */
    private TrackerInterface $progressTracker;

    /**
     * TrackerMiddleware constructor.
     *
     * @param TrackerInterface $progressTracker Tracks activity progress using steps
     */
    public function __construct(TrackerInterface $progressTracker)
    {
        $this->progressTracker = $progressTracker;
    }

    /**
     * {@inheritDoc}
     */
    public function pass(PromiseInterface $responsePromise): PromiseInterface
    {
        $responseTrackedPromise = $this->track($responsePromise);

        return $responseTrackedPromise;
    }

    /**
     * Fires whenever a single body chunk is received (updating tracking reports)
     *
     * @param string $bodyChunk Received body chunk
     *
     * @return void
     */
    protected function onBodyChunk(string $bodyChunk): void
    {
        $stepCount = strlen($bodyChunk);

        $this->progressTracker->advance($stepCount);
    }

    /**
     * Fires whenever a response is received completely and no more body chunks are expected (stopping tracking)
     *
     * @return void
     */
    protected function onComplete(): void
    {
        $this->progressTracker->finish();
    }

    private function track(PromiseInterface $responsePromise): PromiseInterface
    {
        // signalling we don't have a max steps count yet (Content-Length header will be parsed in future).
        $this->progressTracker->setMaxSteps(-1);

        $this->progressTracker->start();

        $responseTrackedPromise = $responsePromise->then(
            function (ResponseInterface $response) {
                $contentLengthValues     = $response->getHeader('Content-Length');
                $contentLengthNormalized = isset($contentLengthValues[0]) ? (int) $contentLengthValues[0] : 0;

                $this->progressTracker->setMaxSteps($contentLengthNormalized);

                /** @var ReadableStreamInterface $responseBody */
                $responseBody = $response->getBody();

                $responseBody->on('data', fn(string $bodyChunk) => $this->onBodyChunk($bodyChunk));
                $responseBody->on('close', fn() => $this->onComplete());

                // propagating resolved value to the next callback, in case we want to chain promise handling.
                // and we do, because we need to unwrap and react to all errors during middleware passes, building
                // our "virtual" stack trace for the client's request() call.
                return $response;
            },
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to track response.', 0, $rejectionReason);
            }
        );

        return $responseTrackedPromise;
    }
}
