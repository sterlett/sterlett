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

namespace Sterlett\Bridge\React\Http\Response;

use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;

/**
 * Defines additional processing logic for the response (e.g. buffering, progress tracking or any other data analysis)
 */
interface MiddlewareInterface
{
    /**
     * Applies processing logic and returns a promise that will be resolved into a PSR-7 response message
     *
     * @param PromiseInterface<ResponseInterface> $responsePromise Promise of response processing
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function pass(PromiseInterface $responsePromise): PromiseInterface;
}
