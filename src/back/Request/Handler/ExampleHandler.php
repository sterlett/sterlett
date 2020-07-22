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

namespace Sterlett\Request\Handler;

use Exception;
use Sterlett\Request\HandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Response;

/**
 * Request handler example
 */
class ExampleHandler implements HandlerInterface
{
    /**
     * Logs handler events
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Handler's unique identifier
     *
     * @var string
     */
    private static string $uid;

    /**
     * ExampleHandler constructor.
     *
     * @param LoggerInterface $logger Logs handler events
     *
     * @throws Exception
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        self::$uid = bin2hex(random_bytes(4));
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $this->logger->debug('An HTTP request has been received.');

        $responseBody = json_encode(
            [
                'uid' => self::$uid,
                'ts'  => time(),
            ]
        );

        $response = new Response(
            200,
            [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            $responseBody
        );

        return $response;
    }
}
