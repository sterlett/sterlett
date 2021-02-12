<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2021 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Browser;

use React\Promise\PromiseInterface;
use RuntimeException;
use Throwable;
use function React\Promise\reject;

class RetryAssistant
{
    public function retry(callable $promisorCallback, int $retryCountMax = 2): PromiseInterface
    {
        $retryCountMaxNormalized = max(1, $retryCountMax);

        $retryAttemptPromise = $this->doRetryAttempt($promisorCallback, 0, $retryCountMaxNormalized);

        return $retryAttemptPromise;
    }

    private function doRetryAttempt(
        callable $promisorCallback,
        int $retryCountCurrent,
        int $retryCountMax
    ): PromiseInterface {
        try {
            /** @var PromiseInterface $promise */
            $promise = $promisorCallback($retryCountCurrent);
        } catch (Throwable $exception) {
            $retryAttemptFailedMessage = sprintf(
                'Unable to receive a promise from the promisor callback, attempt %d/%d (retry assistant).',
                $retryCountCurrent,
                $retryCountMax
            );

            $reason = new RuntimeException($retryAttemptFailedMessage, 0, $exception);

            return reject($reason);
        }

        $promiseHardened = $this->hardenize($promisorCallback, $retryCountCurrent, $retryCountMax, $promise);

        return $promiseHardened;
    }

    private function hardenize(
        callable $promisorCallback,
        int $retryCountCurrent,
        int $retryCountMax,
        PromiseInterface $promise
    ): PromiseInterface
    {
        $promiseHardened = $promise->then(
            null,
            function (Throwable $rejectionReason) use ($promisorCallback, $retryCountCurrent, $retryCountMax) {
                if ($retryCountCurrent < $retryCountMax) {
                    ++$retryCountCurrent;

                    return $this->doRetryAttempt($promisorCallback, $retryCountCurrent, $retryCountMax);
                }

                throw new RuntimeException(
                    "Unable to resolve a promise, max retries: $retryCountMax (retry assistant).",
                    0,
                    $rejectionReason
                );
            }
        );

        return $promiseHardened;
    }
}
