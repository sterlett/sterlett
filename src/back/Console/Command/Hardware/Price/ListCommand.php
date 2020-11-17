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

use ArrayIterator;
use Iterator;
use IteratorIterator;
use RuntimeException;
use Sterlett\Hardware\Price\BlockingProviderInterface;
use Sterlett\Hardware\PriceInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Traversable;

/**
 * Renders a list with hardware prices for the category, defined by the configured price provider.
 *
 * The microservice scope presents logic for extracting data from the promises, for environment with blocking I/O.
 */
class ListCommand extends BaseCommand
{
    /**
     * Retrieves hardware prices in the traditional, blocking I/O way (using await calls)
     *
     * @var BlockingProviderInterface
     */
    private BlockingProviderInterface $priceProvider;

    /**
     * ListCommand constructor.
     *
     * @param BlockingProviderInterface $priceProvider Retrieves hardware prices (blocking I/O)
     * @param string                    $description   Description for the command
     */
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
        if (!$output instanceof ConsoleOutputInterface) {
            $outputTypeMismatchExceptionMessage = sprintf(
                "Output is expected as the instance of '%s' to execute this command.",
                ConsoleOutputInterface::class
            );

            throw new RuntimeException($outputTypeMismatchExceptionMessage);
        }

        $tableSection = $output->section();

        $outputTable = new Table($tableSection);
        $outputTable->setHeaders(['Hardware name', 'Prices']);

        $priceIterator = $this->retrievePrices();
        $priceIterator->rewind();

        $questionHelper  = $this->getHelper('question');
        $questionSection = $output->section();

        // rendering the first 5 positions.
        $sectionNumber = 1;
        $this->renderTableSection($outputTable, $priceIterator, 5, $sectionNumber);

        // render more rows if needed.
        for (; $priceIterator->valid();) {
            $questionContinue = new ConfirmationQuestion('Next section? [Y/n]');

            $isMoreRowsNeeded = $questionHelper->ask($input, $questionSection, $questionContinue);
            $questionSection->clear(2);

            if (!$isMoreRowsNeeded) {
                break 1;
            }

            $tableSection->clear();

            ++$sectionNumber;
            $this->renderTableSection($outputTable, $priceIterator, 5, $sectionNumber);
        }

        return parent::SUCCESS;
    }

    /**
     * Renders a list with hardware prices using the given table helper
     *
     * @param Table              $outputTable   Output table helper for price data visualization
     * @param Iterator<iterable> $priceIterator Iterator for hardware prices from the configured providers
     * @param int                $rowCount      Count of rows for the table section to render
     * @param int                $sectionNumber Number to display sections browsing progress
     *
     * @return void
     */
    private function renderTableSection(
        Table $outputTable,
        Iterator $priceIterator,
        int $rowCount,
        int $sectionNumber
    ): void {
        $outputTable->setRows([]);

        $outputTable->addRows(
            [
                [new TableCell('Section ' . $sectionNumber, ['colspan' => 2])],
                new TableSeparator(),
            ]
        );

        $rowRemainsCount = $rowCount;

        for (; $rowRemainsCount > 0;) {
            if (!$priceIterator->valid()) {
                break 1;
            }

            if ($rowRemainsCount != $rowCount) {
                $outputTable->addRow(new TableSeparator());
            }

            /** @var Traversable<PriceInterface>|PriceInterface[] $hardwarePrices */
            $priceBySellerList = $priceIterator->current();

            $this->addRow($outputTable, $priceBySellerList);

            $priceIterator->next();
            --$rowRemainsCount;
        }

        $outputTable->render();
    }

    /**
     * Creates and adds a new row with hardware price data for the output table
     *
     * @param Table    $outputTable       Output table helper for price data visualization
     * @param iterable $priceBySellerList Collection of hardware prices from the different sellers
     *
     * @return void
     */
    private function addRow(Table $outputTable, iterable $priceBySellerList): void
    {
        $itemName        = '';
        $priceListAsText = '';

        if ($priceBySellerList instanceof Traversable) {
            $priceBySellerListIterator = new IteratorIterator($priceBySellerList);
        } else {
            $priceBySellerListIterator = new ArrayIterator($priceBySellerList);
        }

        $priceBySellerListIterator->rewind();

        for (; $priceBySellerListIterator->valid();) {
            /** @var PriceInterface $hardwarePrice */
            $hardwarePrice = $priceBySellerListIterator->current();

            if (empty($itemName)) {
                $itemName = $hardwarePrice->getHardwareName();
            }

            $priceAsText     = $this->formatPrice($hardwarePrice);
            $priceListAsText .= $priceAsText;

            $priceBySellerListIterator->next();

            if ($priceBySellerListIterator->valid()) {
                $priceListAsText .= PHP_EOL;
            }
        }

        $tableRow = [
            $itemName,
            $priceListAsText,
        ];

        $outputTable->addRow($tableRow);
    }

    /**
     * Returns an iterator for price DTOs, which have been extracted from the configured provider.
     *
     * Elements of returning collection are Traversable<PriceInterface> sequences, keyed by the hardware identifiers.
     *
     * @return Iterator<iterable>
     */
    private function retrievePrices(): Iterator
    {
        $hardwarePrices = $this->priceProvider->getPrices();

        if ($hardwarePrices instanceof Iterator) {
            return $hardwarePrices;
        }

        if ($hardwarePrices instanceof Traversable) {
            return new IteratorIterator($hardwarePrices);
        } else {
            return new ArrayIterator($hardwarePrices);
        }
    }

    /**
     * Returns price data as string, formatted for the console table output
     *
     * @param PriceInterface $hardwarePrice Hardware price DTO
     *
     * @return string
     */
    private function formatPrice(PriceInterface $hardwarePrice): string
    {
        $sellerIdentifier = $hardwarePrice->getSellerIdentifier();
        $priceAmount      = $hardwarePrice->getAmount();
        $pricePrecision   = $hardwarePrice->getPrecision();
        $priceCurrency    = $hardwarePrice->getCurrency();

        if ($pricePrecision > 0) {
            $priceAmountDenormalized = substr_replace($priceAmount, ',', -$pricePrecision, 0);
        } else {
            $priceAmountDenormalized = $priceAmount;
        }

        $priceFormatted = sprintf('%s: %s %s', $sellerIdentifier, $priceAmountDenormalized, $priceCurrency);

        return $priceFormatted;
    }
}
