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

namespace Sterlett\Hardware\Benchmark\Provider;

use Exception;
use React\EventLoop\LoopInterface;
use RuntimeException;
use Sterlett\Hardware\Benchmark\BlockingProviderInterface;
use Sterlett\Hardware\Benchmark\ProviderInterface;
use function Clue\React\Block\await;

/**
 * Obtains a list with hardware benchmark results from the given asynchronous provider for environment with blocking I/O
 */
class BlockingProvider implements BlockingProviderInterface
{
    /**
     * Retrieves hardware benchmark data (async approach)
     *
     * @var ProviderInterface
     */
    private ProviderInterface $benchmarkProvider;

    /**
     * Event loop that is used by the given benchmark provider
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * BlockingProvider constructor.
     *
     * @param ProviderInterface $benchmarkProvider Retrieves hardware benchmark data (async approach)
     * @param LoopInterface     $loop              Event loop that is used by the given benchmark provider
     */
    public function __construct(ProviderInterface $benchmarkProvider, LoopInterface $loop)
    {
        $this->benchmarkProvider = $benchmarkProvider;
        $this->loop              = $loop;
    }

    /**
     * {@inheritDoc}
     */
    public function getBenchmarks(): iterable
    {
        $benchmarkListPromise = $this->benchmarkProvider->getBenchmarks();

        try {
            return await($benchmarkListPromise, $this->loop);
        } catch (Exception $rejectionReason) {
            throw new RuntimeException(
                'Unable to extract benchmarks from the asynchronous provider.',
                0,
                $rejectionReason
            );
        }
    }
}
