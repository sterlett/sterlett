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

namespace Sterlett\Console\Command\Hardware\Price;

use Sterlett\Hardware\Price\BlockingProviderInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

class ListCommand extends BaseCommand
{
    /**
     * @var BlockingProviderInterface
     */
    private BlockingProviderInterface $priceProvider;

    public function __construct(BlockingProviderInterface $priceProvider, string $description)
    {
        parent::__construct();

        $this->priceProvider = $priceProvider;

        $this->setDescription($description);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $prices = $this->priceProvider->getPrices();

        foreach ($prices as $hardwareIdentifier => $hardwarePrices) {
            // todo: better price rendering

            if ($hardwarePrices instanceof Traversable) {
                $priceArray = iterator_to_array($hardwarePrices);
            } else {
                $priceArray = (array) $hardwarePrices;
            }

            var_dump($hardwareIdentifier, $priceArray);
        }

        return parent::SUCCESS;
    }
}
