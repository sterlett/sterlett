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

namespace Sterlett\Console\Command\Hardware\Ratio;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Calculates Value/Benchmark rating for the list of hardware items
 */
class CalculateCommand extends BaseCommand
{
    /**
     * Tag name, to search for the live V/B ratio provider
     *
     * @var string
     */
    private const PROVIDER_LIVE = 'live';

    /**
     * Collects all registered V/B ratio provider implementations for usage within the command
     *
     * @var ServiceLocator
     */
    private ServiceLocator $ratioProviderLocator;

    /**
     * CalculateCommand constructor.
     *
     * @param ServiceLocator $ratioProviderLocator Collects all registered V/B ratio provider implementations
     * @param string         $description          Command description
     */
    public function __construct(ServiceLocator $ratioProviderLocator, string $description)
    {
        parent::__construct();

        $this->ratioProviderLocator = $ratioProviderLocator;

        $this->setDescription($description);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ratioProvider = $this->ratioProviderLocator->get(self::PROVIDER_LIVE);

        // todo: prepare view

        $ratios = $ratioProvider->getRatios();

        // todo: apply sorting by the ratio value (console-only scope)
        // todo: render view

        return parent::SUCCESS;
    }
}
