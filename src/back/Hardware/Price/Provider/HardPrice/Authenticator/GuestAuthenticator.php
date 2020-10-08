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

namespace Sterlett\Hardware\Price\Provider\HardPrice\Authenticator;

use Exception;
use Psr\Http\Message\ResponseInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\ClientInterface;
use Sterlett\Hardware\Price\Provider\HardPrice\Authentication;

class GuestAuthenticator
{
    private ClientInterface $httpClient;

    private string $authenticationUri;

    public function __construct(ClientInterface $httpClient, string $authenticationUri)
    {
        $this->httpClient        = $httpClient;
        $this->authenticationUri = $authenticationUri;
    }

    public function authenticate(): PromiseInterface
    {
        $authenticationDeferred = new Deferred();

        $responsePromise = $this->httpClient->request('GET', $this->authenticationUri);

        $responsePromise->then(
            function (ResponseInterface $response) use ($authenticationDeferred) {
                // todo: extract auth data from the response

                $cookies = [];

                $authentication = new Authentication();
                $authentication->setCsrfToken('test-token');

                $authenticationDeferred->resolve($authentication);
            },
            function (Exception $rejectionReason) use ($authenticationDeferred) {
                $reason = new RuntimeException('Unable to authenticate (guest).', 0, $rejectionReason);

                $authenticationDeferred->reject($reason);
            }
        );

        $authenticationPromise = $authenticationDeferred->promise();

        return $authenticationPromise;
    }
}
