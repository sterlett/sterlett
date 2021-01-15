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

namespace Sterlett\Hardware\VBRatio\Provider;

use React\EventLoop\LoopInterface;
use RuntimeException;
use Sterlett\Hardware\VBRatio\BlockingProviderInterface;
use Sterlett\Hardware\VBRatio\ProviderInterface;
use Throwable;
use function Clue\React\Block\await;

/**
 * Resolves a Value/Benchmark ratio records for environment with blocking I/O
 */
class BlockingProvider implements BlockingProviderInterface
{
    /**
     * Resolves a Value/Benchmark ratio for available hardware items (async approach)
     *
     * @var ProviderInterface
     */
    private ProviderInterface $ratioProvider;

    /**
     * A reference to the event loop instance, which is used by the given ratio provider
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * BlockingProvider constructor.
     *
     * @param ProviderInterface $ratioProvider Resolves a Value/Benchmark ratio for hardware items (async approach)
     * @param LoopInterface     $loop          Event loop instance, which is used by the given ratio provider
     */
    public function __construct(ProviderInterface $ratioProvider, LoopInterface $loop)
    {
        $this->ratioProvider = $ratioProvider;
        $this->loop          = $loop;
    }

    /**
     * {@inheritDoc}
     */
    public function getRatios(): iterable
    {
        $ratioListPromise = $this->ratioProvider->getRatios();

        try {
            return await($ratioListPromise, $this->loop);
        } catch (Throwable $rejectionReason) {
            throw new RuntimeException(
                'Unable to extract V/B ratio records from the asynchronous provider.',
                0,
                $rejectionReason
            );
        }
    }
}
