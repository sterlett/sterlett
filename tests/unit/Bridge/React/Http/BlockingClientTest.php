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
use Psr\Log\LoggerInterface;
use React\EventLoop\StreamSelectLoop;
use React\Http\Browser;
use Sterlett\Bridge\React\Http\BlockingClient;

/**
 * Tests blocking HTTP client, based on ReactPHP Browser, for valid response processing
 */
final class BlockingClientTest extends TestCase
{
    /**
     * Blocking client instance
     *
     * @var BlockingClient
     */
    private BlockingClient $client;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $loop    = new StreamSelectLoop();
        $browser = new Browser($loop);

        $loggerStub = $this->createStub(LoggerInterface::class);

        $this->client = new BlockingClient($loggerStub, $browser, $loop, ['timeout' => 0.350]);
    }

    /**
     * Ensures that response is received using client API and buffered into the memory (synchronously)
     *
     * @return void
     */
    public function testResponseIsReceivedAndBufferedBySynchronousRequest(): void
    {
        $dataUri = 'http://ip-api.com/json';

        $responsePromise = $this->client->request('GET', $dataUri);

        $responseExtracted  = null;
        $exceptionExtracted = null;

        $responsePromise->then(
            function (ResponseInterface $response) use (&$responseExtracted) {
                $responseExtracted = $response;
            },
            function (Exception $exception) use (&$exceptionExtracted) {
                $exceptionExtracted = (string) $exception;
            }
        );

        $this->assertNull(
            $exceptionExtracted,
            "A successful 'request' call should not be rejected with an exception as a reason."
        );

        // blocking approach guarantees a fully buffered response here.
        /** @var ResponseInterface $responseExtracted */

        $this->assertInstanceOf(
            ResponseInterface::class,
            $responseExtracted,
            "Response should be assigned right after promise call 'then' returns."
        );

        $body = $responseExtracted->getBody();

        $this->assertInstanceOf(
            StreamInterface::class,
            $body,
            'Response body should be represented by PSR-7 StreamInterface.'
        );

        $bodyAsString = (string) $responseExtracted->getBody();

        $this->assertStringContainsString(
            '"status":"success"',
            $bodyAsString,
            'Response body should contain an expected content.'
        );
    }
}
