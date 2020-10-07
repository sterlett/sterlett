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

use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Dto\Hardware\Price;
use Sterlett\Hardware\Price\Provider\HardPrice\IdExtractor;
use Sterlett\Hardware\Price\ProviderInterface;
use Throwable;

/**
 * Obtains a list with hardware prices from the HardPrice website
 *
 * @see https://hardprice.ru
 */
class HardPriceProvider implements ProviderInterface
{
    /**
     * @var IdExtractor
     */
    private IdExtractor $idExtractor;

    public function __construct(IdExtractor $idExtractor)
    {
        $this->idExtractor = $idExtractor;
    }

    /**
     * @inheritDoc
     */
    public function getPrices(): PromiseInterface
    {
        $retrievingDeferred = new Deferred();

        $idListPromise = $this->idExtractor->getIdentifiers();

        $idListPromise->then(
            function (iterable $identifiers) use ($retrievingDeferred) {
                $requester = function (iterable $identifiers) {
                    $i = 0;

                    foreach ($identifiers as $identifier) {
                        // todo: actual requests for data by id

                        $price = new Price();
                        $price->setHardwareName('Test');
                        $price->setSellerIdentifier('seller1-item' . $identifier);
                        $price->setAmount(10);
                        $price->setPrecision(4);
                        $price->setCurrency('RUR');

                        yield $price;

                        if (++$i >= 5) {
                            break 1;
                        }
                    }
                };

                $retrievingDeferred->resolve($requester($identifiers));
            },
            function (Throwable $rejectionReason) use ($retrievingDeferred) {
                $reason = new RuntimeException('Unable to retrieve prices.', 0, $rejectionReason);

                $retrievingDeferred->reject($reason);
            }
        );

        $priceListPromise = $retrievingDeferred->promise();

        return $priceListPromise;
    }
}
