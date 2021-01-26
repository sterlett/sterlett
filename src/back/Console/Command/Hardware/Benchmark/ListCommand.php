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

use Iterator;
use RuntimeException;
use Sterlett\Hardware\Benchmark\BlockingProviderInterface as BenchmarkProviderInterface;
use Sterlett\Hardware\BenchmarkInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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

            $providerIdNormalized = (string) $providerId;

            $invalidInterfaceExceptionMessage = sprintf(
                "Benchmark provider '%s' should implement '%s'.",
                $providerIdNormalized,
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
        if (!$output instanceof ConsoleOutputInterface) {
            $outputTypeMismatchExceptionMessage = sprintf(
                "Output is expected as the instance of '%s' to execute this command.",
                ConsoleOutputInterface::class
            );

            throw new RuntimeException($outputTypeMismatchExceptionMessage);
        }

        $tableSection = $output->section();

        $outputTable = new Table($tableSection);
        $outputTable->setHeaders(['Provider', 'Hardware name', 'Benchmark rating']);

        $benchmarkIterator = $this->retrieveBenchmarks();
        $benchmarkIterator->rewind();

        $questionHelper = $this->getHelper('question');
        // assigning a separate output section for the question line (and the answer line) to prevent content
        // duplication after table rewriting, see https://github.com/symfony/console/blob/v5.1.0/Helper/Table.php#L284
        $questionSection = $output->section();

        // rendering the first 10 rows.
        $this->renderTableSection($outputTable, $benchmarkIterator, 10);

        // render more rows if needed.
        for (; $benchmarkIterator->valid();) {
            $questionContinue = new ConfirmationQuestion('Show more? [Y/n]');

            $isMoreRowsNeeded = $questionHelper->ask($input, $questionSection, $questionContinue);
            $questionSection->clear(2);

            if (!$isMoreRowsNeeded) {
                break 1;
            }

            $this->renderTableSection($outputTable, $benchmarkIterator, 1);
        }

        return parent::SUCCESS;
    }

    /**
     * Renders a list with hardware benchmark values using the given table helper
     *
     * @param Table                        $outputTable       Output table helper for benchmark data visualization
     * @param Iterator<BenchmarkInterface> $benchmarkIterator Iterator for benchmarks from the configured providers
     * @param int                          $rowCount          Count of rows for the table section to render
     *
     * @return void
     */
    private function renderTableSection(Table $outputTable, Iterator $benchmarkIterator, int $rowCount): void
    {
        $rowRemainsCount = $rowCount;

        for (; $rowRemainsCount > 0;) {
            if (!$benchmarkIterator->valid()) {
                break 1;
            }

            $providerId = $benchmarkIterator->key();

            /** @var BenchmarkInterface $benchmark */
            $benchmark = $benchmarkIterator->current();

            $this->appendTableRow($outputTable, $providerId, $benchmark);

            $benchmarkIterator->next();
            --$rowRemainsCount;
        }
    }

    /**
     * Creates and appends a new row with benchmark data for the output table
     *
     * @param Table              $outputTable Output table helper for benchmark data visualization
     * @param string             $providerId  Benchmark provider identifier
     * @param BenchmarkInterface $benchmark   Benchmark instance
     *
     * @return void
     */
    private function appendTableRow(Table $outputTable, string $providerId, BenchmarkInterface $benchmark): void
    {
        $benchmarkHardwareName = $benchmark->getHardwareName();
        $benchmarkValue        = $benchmark->getValue();

        // todo: extract formatter service
        $benchmarkValueFormatted = number_format((float) $benchmarkValue, 0);

        $tableRow = [
            $providerId,
            $benchmarkHardwareName,
            $benchmarkValueFormatted,
        ];

        $outputTable->appendRow($tableRow);
    }

    /**
     * Returns an iterator for benchmarks from the configured providers
     *
     * @return Iterator<BenchmarkInterface>
     */
    private function retrieveBenchmarks(): Iterator
    {
        foreach ($this->benchmarkProviders as $providerId => $benchmarkProvider) {
            $providerIdNormalized = (string) $providerId;

            $benchmarks = $benchmarkProvider->getBenchmarks();

            foreach ($benchmarks as $benchmark) {
                yield $providerIdNormalized => $benchmark;
            }
        }
    }
}
