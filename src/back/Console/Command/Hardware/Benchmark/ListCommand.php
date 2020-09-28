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

namespace Sterlett\Console\Command\Hardware\Benchmark;

use Sterlett\Hardware\Benchmark\ProviderInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

/**
 * Renders a list with hardware benchmark values for the category, specified by the given benchmark providers
 */
class ListCommand extends BaseCommand
{
    /**
     * A list with benchmark providers, keyed by names/identifiers
     *
     * @var Traversable<ProviderInterface>|ProviderInterface[]
     */
    private iterable $benchmarkProviders;

    /**
     * ListCommand constructor.
     *
     * @param iterable $benchmarkProviders A list with benchmark providers, keyed by names/identifiers
     * @param string   $description        Description for the command
     */
    public function __construct(iterable $benchmarkProviders, string $description)
    {
        parent::__construct();

        $this->benchmarkProviders = $benchmarkProviders;

        $this->setDescription($description);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // todo: service call

        return parent::SUCCESS;
    }
}
