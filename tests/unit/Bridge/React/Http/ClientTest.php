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

namespace Sterlett\Tests\Bridge\React\Http;

use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\StreamSelectLoop;
use React\Http\Browser;
use Sterlett\Bridge\React\Http\Client;
use Sterlett\Bridge\React\Http\Response\Middleware\BuffererMiddleware;
use function Clue\React\Block\await;

/**
 * Tests HTTP client, based on ReactPHP Browser, for valid response processing
 */
final class ClientTest extends TestCase
{
    /**
     * Event loop
     *
     * @var StreamSelectLoop
     */
    private StreamSelectLoop $loop;

    /**
     * Client instance
     *
     * @var Client
     */
    private Client $client;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->loop     = new StreamSelectLoop();
        $browser        = new Browser($this->loop);
        $middlewareList = [new BuffererMiddleware()];

        $this->client = new Client($browser, $middlewareList);
    }

    /**
     * Ensures that response is received using client API and buffered into the memory (synchronously)
     *
     * @return void
     */
    public function testResponseIsReceivedAndBuffered(): void
    {
        $dataUri = 'http://ip-api.com/json';

        $responsePromise = $this->client->request('GET', $dataUri);
        $response        = null;

        try {
            $response = await($responsePromise, $this->loop, 5.0);
        } catch (Exception $rejectionReason) {
            $failReasonMessage = sprintf(
                'A response promise after client request call has been rejected with a reason: %s',
                (string) $rejectionReason
            );

            $this->fail($failReasonMessage);
        }

        $this->assertInstanceOf(
            ResponseInterface::class,
            $response,
            'Promise should be resolved into the instance of ' . ResponseInterface::class . '.'
        );

        $body = $response->getBody();

        $this->assertInstanceOf(
            StreamInterface::class,
            $body,
            'Response body should be represented by ' . StreamInterface::class . '.'
        );

        $bodyAsString = (string) $body;

        $this->assertStringContainsString(
            '"status":"success"',
            $bodyAsString,
            'Response body should contain an expected content.'
        );
    }
}
