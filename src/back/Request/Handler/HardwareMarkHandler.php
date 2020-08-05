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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as RequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Response;
use Sterlett\Request\HandlerInterface;
use Sterlett\Request\Uri\Match;
use Sterlett\Request\Uri\MatcherInterface;

/**
 * Handles requests for information about "price/benchmark" ratio for the different hardware categories
 */
class HardwareMarkHandler implements HandlerInterface
{
    /**
     * Action name to render a list with "price/benchmark" ratio for CPUs
     *
     * @var string
     */
    public const ACTION_CPU_LIST = 'app.request.handler.hardware_mark_handler.action_cpu_list';

    /**
     * Logs handler events
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Finds context to decide which action should be used to generate a response for the given request
     *
     * @var MatcherInterface
     */
    private MatcherInterface $uriMatcher;

    /**
     * HardwareMarkHandler constructor.
     *
     * @param LoggerInterface  $logger     Logs handler events
     * @param MatcherInterface $uriMatcher Finds context to decide which action should be used to generate a response
     *                                     for the given request
     */
    public function __construct(LoggerInterface $logger, MatcherInterface $uriMatcher)
    {
        $this->logger     = $logger;
        $this->uriMatcher = $uriMatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $this->logger->debug('An HTTP request has been received.');

        $actionName = $this->resolveActionName($request);

        if (self::ACTION_CPU_LIST === $actionName) {
            $response = $this->getCpuListResponse();
        } else {
            $response = $this->getNotFoundResponse();
        }

        return $response;
    }

    /**
     * Returns handler's action name to generate an appropriate response
     *
     * @param RequestInterface $request PSR-7 request message
     *
     * @return string|null
     */
    private function resolveActionName(RequestInterface $request): ?string
    {
        $requestUri     = $request->getUri();
        $requestUriPath = $requestUri->getPath();

        $uriMatch = $this->uriMatcher->match($requestUriPath);

        if (!$uriMatch instanceof Match) {
            return null;
        }

        $uriMatchActionName = $uriMatch->getActionName();

        return $uriMatchActionName;
    }

    /**
     * Returns response with "price/benchmark" ratio for CPUs
     *
     * @return Response
     */
    private function getCpuListResponse(): Response
    {
        $responseBody = json_encode(
            [
                'items' => [],
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

    /**
     * Returns response for the case when a given URI is not found
     *
     * @return Response
     */
    private function getNotFoundResponse(): Response
    {
        $responseBody = json_encode(
            [
                'errors' => [
                    [
                        'message' => 'Resource is not found.',
                    ],
                ],
            ]
        );

        $response = new Response(
            404,
            [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            $responseBody
        );

        return $response;
    }
}
