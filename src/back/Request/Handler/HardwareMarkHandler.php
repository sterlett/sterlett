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
 *
 * todo: inject service locator to manage different actions as standalone services
 *       https://symfony.com/doc/current/service_container/service_subscribers_locators.html
 */
class HardwareMarkHandler implements HandlerInterface
{
    /**
     * Action name to render a list with "price/benchmark" ratio for CPUs
     *
     * @var string
     */
    private const ACTION_CPU_LIST = 'app.request.handler.action.cpu_list_action';

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
     * Represents a list with hardware statistics from the CPU category
     *
     * @var string
     */
    private string $cpuData;

    /**
     * HardwareMarkHandler constructor.
     *
     * @param LoggerInterface  $logger     Logs handler events
     * @param MatcherInterface $uriMatcher Finds context to decide which action should be used to generate a response
     *                                     for the given request
     * @param string           $cpuData    Represents a list with hardware statistics from the CPU category
     */
    public function __construct(LoggerInterface $logger, MatcherInterface $uriMatcher, string $cpuData = '')
    {
        $this->logger     = $logger;
        $this->uriMatcher = $uriMatcher;
        $this->cpuData    = $cpuData;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $this->logger->debug('An HTTP request has been received.');

        $actionName = $this->resolveActionName($request);

        if (self::ACTION_CPU_LIST === $actionName) {
            // todo: handle chunked data (in "pending" status, if buffer is used)
            if (is_string($this->cpuData)) {
                $response = $this->getCpuListResponse();
            } else {
                $response = $this->getCpuListNotReadyResponse();
            }
        } else {
            $response = $this->getNotFoundResponse();
        }

        return $response;
    }

    /**
     * Adds data, which represents a list with hardware statistics from the CPU category, to the handler's cache
     *
     * @param string $cpuData Represents a list with hardware statistics from the CPU category
     *
     * @return void
     */
    public function addCpuData(string $cpuData): void
    {
        $this->cpuData .= $cpuData;
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
        $responseBody = $this->cpuData;

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
     * Returns response for the case when CPU list is not received yet
     *
     * @return Response
     */
    private function getCpuListNotReadyResponse(): Response
    {
        $responseBody = json_encode(
            [
                'errors' => [
                    [
                        'message' => 'Resource is not ready.',
                    ],
                ],
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
