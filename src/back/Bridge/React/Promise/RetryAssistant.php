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

namespace Sterlett\Bridge\React\Promise;

use React\Promise\PromiseInterface;
use RuntimeException;
use Throwable;
use function React\Promise\reject;

/**
 * Will try to resolve a promise from the promisor callback, while the configured retry counter is valid
 */
class RetryAssistant
{
    /**
     * Returns a promise that will be resolved when the promisor's retval is successfully settled, and, will be
     * rejected, when the number of resolving attempts is exceeded.
     *
     * The result value of the "hardened" promise will be forwarded from the given source promise "as is".
     *
     * @param callable $promisorCallback A callback that must return a valid instance of the PromiseInterface
     * @param int      $retryCountMax    Max retries, to call a promisor callback and resolve a source promise
     *
     * @return PromiseInterface<mixed>
     */
    public function retry(callable $promisorCallback, int $retryCountMax = 2): PromiseInterface
    {
        $retryCountMaxNormalized = max(1, $retryCountMax);

        $retryAttemptPromise = $this->doRetryAttempt($promisorCallback, 0, $retryCountMaxNormalized);

        return $retryAttemptPromise;
    }

    /**
     * Registers a retry attempt in the resolving chain for the "source" promise and maintains a retry counter state
     *
     * @param callable $promisorCallback  A callback that must return a valid instance of the PromiseInterface
     * @param int      $retryCountCurrent Count of remaining retries
     * @param int      $retryCountMax     Max retries, to call a promisor callback and resolve a source promise
     *
     * @return PromiseInterface<mixed>
     */
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

    /**
     * Adds a hook to the existing promise resolving chain, which stops the default exception propagation and tries to
     * resolve a source promise once again (while the retry counter is valid)
     *
     * @param callable         $promisorCallback  A callback that must return a valid instance of the PromiseInterface
     * @param int              $retryCountCurrent Count of remaining retries
     * @param int              $retryCountMax     Max retries, to call a promisor callback and resolve a source promise
     * @param PromiseInterface $promise           A source promise, from the last promisor call
     *
     * @return PromiseInterface<mixed>
     */
    private function hardenize(
        callable $promisorCallback,
        int $retryCountCurrent,
        int $retryCountMax,
        PromiseInterface $promise
    ): PromiseInterface {
        $promiseHardened = $promise->then(
            null,
            function (Throwable $rejectionReason) use ($promisorCallback, $retryCountCurrent, $retryCountMax) {
                if ($retryCountCurrent < $retryCountMax) {
                    $retryCountNew = $retryCountCurrent + 1;

                    // preventing further exception bubbling and resuming a normal "resolve" settlement,
                    // while we still have available "retries".
                    return $this->doRetryAttempt($promisorCallback, $retryCountNew, $retryCountMax);
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
