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

namespace Sterlett\Hardware\Price\Provider;

use React\EventLoop\LoopInterface;
use RuntimeException;
use Sterlett\Hardware\Price\BlockingProviderInterface;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;
use function Clue\React\Block\await;

/**
 * Obtains a list with hardware prices from the given asynchronous provider for environment with blocking I/O
 */
class BlockingProvider implements BlockingProviderInterface
{
    /**
     * Retrieves hardware prices data (async approach)
     *
     * @var ProviderInterface
     */
    private ProviderInterface $priceProvider;

    /**
     * Event loop that is used by the given price provider
     *
     * @var LoopInterface
     */
    private LoopInterface $loop;

    /**
     * BlockingProvider constructor.
     *
     * @param ProviderInterface $priceProvider Retrieves hardware prices data (async approach)
     * @param LoopInterface     $loop          Event loop that is used by the given price provider
     */
    public function __construct(ProviderInterface $priceProvider, LoopInterface $loop)
    {
        $this->priceProvider = $priceProvider;
        $this->loop          = $loop;
    }

    /**
     * @inheritDoc
     */
    public function getPrices(): iterable
    {
        $priceListPromise = $this->priceProvider->getPrices();

        try {
            return await($priceListPromise, $this->loop);
        } catch (Throwable $rejectionReason) {
            throw new RuntimeException(
                'Unable to extract prices from the asynchronous provider.',
                0,
                $rejectionReason
            );
        }
    }
}
