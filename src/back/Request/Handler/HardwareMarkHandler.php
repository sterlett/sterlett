<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020-2021 Pavel Petrov <itnelo@gmail.com>.
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
use React\Http\Message\Response;
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
    public const ACTION_CPU_LIST = 'app.request.handler.action.cpu_list_action';

    /**
     * Action name to render a dataset for the best deals in the CPU category
     *
     * @var string
     */
    public const ACTION_CPU_DEALS = 'app.request.handler.action.cpu_deals_action';

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
     * Deals data for the CPU category
     *
     * @var string
     */
    private string $cpuDealsData;

    /**
     * HardwareMarkHandler constructor.
     *
     * @param LoggerInterface  $logger       Logs handler events
     * @param MatcherInterface $uriMatcher   Finds context to decide which action should be used to generate a response
     * @param string           $cpuData      Represents a list with hardware statistics from the CPU category
     * @param string           $cpuDealsData Deals data for the CPU category
     */
    public function __construct(
        LoggerInterface $logger,
        MatcherInterface $uriMatcher,
        string $cpuData = '',
        string $cpuDealsData = ''
    ) {
        $this->logger       = $logger;
        $this->uriMatcher   = $uriMatcher;
        $this->cpuData      = $cpuData;
        $this->cpuDealsData = $cpuDealsData;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $this->logger->debug('An HTTP request has been received.');

        $actionName = $this->resolveActionName($request);

        // list
        if (self::ACTION_CPU_LIST === $actionName) {
            // todo: handle chunked data (in "pending" status, if buffer is used)
            //$response = $this->getCpuListNotReadyResponse();

            return $this->getCpuListResponse();
        }

        // deals
        if (self::ACTION_CPU_DEALS === $actionName) {
            return $this->getCpuDealsResponse();
        }

        $response = $this->getNotFoundResponse();

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
     * Adds data, representing a set of deals for the CPU category, to the handler's cache
     *
     * @param string $cpuDealsData CPU deals data
     *
     * @return void
     */
    public function addCpuDealsData(string $cpuDealsData): void
    {
        $this->cpuDealsData .= $cpuDealsData;
    }

    /**
     * Resets handler's state for the specified action
     *
     * @param string $actionName Action name
     *
     * @return void
     */
    public function resetState(string $actionName): void
    {
        if (self::ACTION_CPU_LIST === $actionName) {
            $this->cpuData = '';

            return;
        }

        $this->cpuDealsData = '';
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
        return $this->getDataResponse($this->cpuData);
    }

    /**
     * Returns a response with data for the best deals in the CPU category
     *
     * @return Response
     */
    private function getCpuDealsResponse(): Response
    {
        return $this->getDataResponse($this->cpuDealsData);
    }

    /**
     * Returns a response with the data payload
     *
     * @param string $payload Data for the response
     *
     * @return Response
     */
    private function getDataResponse(string $payload): Response
    {
        $response = new Response(
            200,
            [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            $payload
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
