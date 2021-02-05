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

use ArrayIterator;
use Iterator;
use IteratorIterator;
use RuntimeException;
use Sterlett\Hardware\Price\SimpleAverageCalculator;
use Sterlett\Hardware\PriceInterface;
use Sterlett\Hardware\VBRatio\BlockingProviderInterface;
use Sterlett\Hardware\VBRatioInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Traversable;

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
     * Indicates that prices will be pulled from the database instead of "live" providers
     *
     * @var string
     */
    private const PROVIDER_DATABASE = 'database';

    /**
     * Collects all registered V/B ratio provider implementations for usage within the command
     *
     * @var ServiceLocator
     */
    private ServiceLocator $ratioProviderLocator;

    /**
     * Encapsulates logic for average amount calculation, for the defined price interface
     *
     * @var SimpleAverageCalculator
     */
    private SimpleAverageCalculator $priceAverageCalculator;

    /**
     * CalculateCommand constructor.
     *
     * @param ServiceLocator          $ratioProviderLocator   Collects all registered V/B ratio provider implementations
     * @param SimpleAverageCalculator $priceAverageCalculator Encapsulates logic for average amount calculation
     * @param string                  $description            Command description
     */
    public function __construct(
        ServiceLocator $ratioProviderLocator,
        SimpleAverageCalculator $priceAverageCalculator,
        string $description
    ) {
        parent::__construct();

        $this->ratioProviderLocator   = $ratioProviderLocator;
        $this->priceAverageCalculator = $priceAverageCalculator;

        $this->setDescription($description);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addOption(
                'source',
                's',
                InputOption::VALUE_REQUIRED,
                'Source for price retrieving: ' . self::PROVIDER_LIVE . ' or ' . self::PROVIDER_DATABASE,
                self::PROVIDER_LIVE
            )
            ->setHelp(
                <<<HELP
The <info>%command.name%</info> command renders a list with Value/Benchmark ratios, calculated for the available hardware items:

    <info>%command.full_name%</info>

You can specify a source type <comment>live</comment> or <comment>database</comment>. Live providers may cause requests to the third-party
web resources and real-time parsing, while the database providers could utilize local cache from previous
price retrieving sessions (as a microservice) and, generally, operates much faster. But live data is more
accurate (used by default). The database switch is:

    <info>%command.full_name% --source=database</info>
HELP
            )
        ;
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
        $outputTable->setHeaders(['Hardware name', 'V/B ratio', 'Benchmark rating', 'Price avg.']);

        $dataSource    = $input->getOption('source');
        $ratioIterator = $this->pullRatios($dataSource);
        $ratioIterator->rewind();

        // in case when there are no price records (or benchmarks).
        if (!$ratioIterator->valid()) {
            $output->writeln('<info>Not enough data to build a ratio table.</info>');

            return parent::SUCCESS;
        }

        $questionHelper  = $this->getHelper('question');
        $questionSection = $output->section();

        // rendering the first 10 rows.
        $this->renderTableSection($outputTable, $ratioIterator, 10);

        // render more rows if needed.
        for (; $ratioIterator->valid();) {
            $questionContinue = new ConfirmationQuestion('Show more? [Y/n]');

            $isMoreRowsNeeded = $questionHelper->ask($input, $questionSection, $questionContinue);
            $questionSection->clear(2);

            if (!$isMoreRowsNeeded) {
                break 1;
            }

            $this->renderTableSection($outputTable, $ratioIterator, 1);
        }

        return parent::SUCCESS;
    }

    /**
     * Returns an iterator for the available V/B ratio records (pulling from the aggregated providers)
     *
     * @param string $dataSource Name of the source for price retrieving (live, database, etc.)
     *
     * @return Iterator
     */
    private function pullRatios(string $dataSource): Iterator
    {
        /** @var BlockingProviderInterface $ratioProvider */
        $ratioProvider = $this->ratioProviderLocator->get($dataSource);

        $ratios = $ratioProvider->getRatios();

        if ($ratios instanceof Traversable) {
            $ratioIterator = new IteratorIterator($ratios);
        } else {
            $ratioIterator = new ArrayIterator($ratios);
        }

        return $ratioIterator;
    }

    /**
     * Renders a list with V/B ratio data using a given table helper
     *
     * @param Table    $outputTable   Output table helper for V/B ratio records visualization
     * @param Iterator $ratioIterator Iterator for ratio objects from the configured providers
     * @param int      $rowCount      Count of rows for the table section to render
     *
     * @return void
     */
    private function renderTableSection(Table $outputTable, Iterator $ratioIterator, int $rowCount): void
    {
        $rowRemainsCount = $rowCount;

        for (; $rowRemainsCount > 0;) {
            if (!$ratioIterator->valid()) {
                break 1;
            }

            /** @var VBRatioInterface $ratio */
            $ratio = $ratioIterator->current();

            $this->appendTableRow($outputTable, $ratio);

            $ratioIterator->next();
            --$rowRemainsCount;
        }
    }

    /**
     * Creates and appends a new row with V/B ratio data for the output table
     *
     * @param Table            $outputTable Output table helper for V/B ratio records visualization
     * @param VBRatioInterface $ratio       V/B ratio calculation record
     *
     * @return void
     */
    private function appendTableRow(Table $outputTable, VBRatioInterface $ratio): void
    {
        // todo: extract formatter services

        // preparing benchmark data.
        $sourceBenchmark = $ratio->getSourceBenchmark();
        $hardwareName    = $sourceBenchmark->getHardwareName();

        $benchmarkValue           = $sourceBenchmark->getValue();
        $benchmarkValueNormalized = number_format((float) $benchmarkValue, 0);
        $benchmarkValueFormatted  = str_pad($benchmarkValueNormalized, 6, ' ', STR_PAD_LEFT);

        // formatting prices.
        $sourcePrices = $ratio->getSourcePrices();
        $priceArray   = [...$sourcePrices];

        /** @var PriceInterface $price */
        $price         = $priceArray[0];
        $priceCurrency = $price->getCurrency();

        $priceValueAverage      = $this->priceAverageCalculator->calculateAverage($priceArray);
        $priceAverageNormalized = number_format((float) $priceValueAverage);
        $priceAverageFormatted  = str_pad($priceAverageNormalized, 6, ' ', STR_PAD_LEFT);
        $priceAverageFormatted  = sprintf('%s %s', $priceAverageFormatted, $priceCurrency);

        $ratioValue = $ratio->getValue();

        $tableRow = [
            $hardwareName,
            $ratioValue,
            $benchmarkValueFormatted,
            $priceAverageFormatted,
        ];

        $outputTable->appendRow($tableRow);
    }
}
