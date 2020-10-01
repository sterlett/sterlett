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

use RuntimeException;
use Sterlett\Hardware\Benchmark\BlockingProviderInterface as BenchmarkProviderInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

/**
 * Renders a list with hardware benchmark values for the category, specified by the given benchmark providers.
 *
 * Microservice scope will present logic for extracting data from the promises for environment with blocking I/O.
 */
class ListCommand extends BaseCommand
{
    /**
     * A list with benchmark providers, keyed by names/identifiers
     *
     * @var Traversable<BenchmarkProviderInterface>|BenchmarkProviderInterface[]
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

        foreach ($benchmarkProviders as $providerId => $benchmarkProvider) {
            if ($benchmarkProvider instanceof BenchmarkProviderInterface) {
                continue;
            }

            $invalidInterfaceExceptionMessage = sprintf(
                "Benchmark provider '%s' should implement '%s'.",
                $providerId,
                BenchmarkProviderInterface::class
            );

            throw new RuntimeException($invalidInterfaceExceptionMessage);
        }

        $this->benchmarkProviders = $benchmarkProviders;

        $this->setDescription($description);
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputTableRows = $this->buildOutputTableRows();

        $outputTable = new Table($output);
        $outputTable->setHeaders(['Provider', 'Hardware name', 'Benchmark value']);
        $outputTable->setRows($outputTableRows);

        $outputTable->render();

        return parent::SUCCESS;
    }

    /**
     * Returns a list with hardware benchmark values for rendering in the table
     *
     * @return array
     */
    private function buildOutputTableRows(): array
    {
        $outputTableRows = [];

        foreach ($this->benchmarkProviders as $providerId => $benchmarkProvider) {
            $providerIdNormalized = (string) $providerId;

            $benchmarks = $benchmarkProvider->getBenchmarks();

            foreach ($benchmarks as $benchmark) {
                $benchmarkHardwareName = $benchmark->getHardwareName();
                $benchmarkValue        = $benchmark->getValue();

                $outputTableRows[] = [
                    $providerIdNormalized,
                    $benchmarkHardwareName,
                    $benchmarkValue,
                ];
            }
        }

        return $outputTableRows;
    }
}
