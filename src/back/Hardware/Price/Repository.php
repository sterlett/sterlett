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

namespace Sterlett\Hardware\Price;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use React\MySQL\ConnectionInterface;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Dto\Hardware\Price;
use Sterlett\Hardware\PriceInterface;
use Throwable;
use Traversable;

/**
 * A storage with price records for the hardware items
 */
class Repository
{
    /**
     * Manages database connection state and sends async queries
     *
     * @var ConnectionInterface
     */
    private ConnectionInterface $databaseConnection;

    /**
     * Table name from which hardware price records will be loaded
     *
     * @var string
     */
    private string $_tableNamePurified;

    /**
     * Repository constructor.
     *
     * @param ConnectionInterface $databaseConnection Manages database connection state and sends async queries
     * @param string              $priceTableName     Table name from which hardware price records will be loaded
     */
    public function __construct(ConnectionInterface $databaseConnection, string $priceTableName)
    {
        $this->databaseConnection = $databaseConnection;

        if (1 !== preg_match('/^[a-z0-9_]+$/', $priceTableName)) {
            throw new InvalidArgumentException('Invalid price table name (repository).');
        }

        $this->_tableNamePurified = $priceTableName;
    }

    /**
     * Returns a promise that resolves to a couple [iterable, ?DateTimeInterface], where the first element is a
     * collection of price records and the second one - the most recent record date or a NULL if there are no records
     *
     * @return PromiseInterface<array>
     */
    public function findByCreatedAtMax(): PromiseInterface
    {
        $statementSelectCreatedAtMax = <<<SQL
            SELECT
                DATE(MAX(created_at)) AS 'created_at'
            FROM
                `{$this->_tableNamePurified}`
            ;
SQL;

        $priceListWithDatePromise = $this->databaseConnection
            // requesting a record date for the select condition.
            ->query($statementSelectCreatedAtMax)
            // hydrating the result value.
            ->then(
                function (QueryResult $queryResult) {
                    $dateFromString = $queryResult->resultRows[0]['created_at'] ?? null;

                    if (!is_string($dateFromString)) {
                        return null;
                    }

                    $dateFrom = new DateTime($dateFromString);

                    return $dateFrom;
                }
            )
            // executing a common fetch query using the resolved date.
            ->then(
                function (?DateTimeInterface $dateFrom) {
                    if (!$dateFrom instanceof DateTimeInterface) {
                        return [[], null];
                    }

                    $dateTo = new DateTime();

                    return $this
                        ->findByCreatedAt($dateFrom, $dateTo)
                        // enhancing a resolve value to include both the records and the max date available.
                        ->then(fn (iterable $hardwarePrices) => [$hardwarePrices, $dateFrom])
                    ;
                }
            )
        ;

        return $priceListWithDatePromise;
    }

    /**
     * Returns a promise that resolves to an iterable collection of price records from the storage.
     *
     * Note: duplicate records (same hardware name, seller and a price tag) will not be included (if any).
     *
     * @param DateTimeInterface $dateFrom The lower datetime boundary for desired collection of price records
     * @param DateTimeInterface $dateTo   The upper datetime boundary
     *
     * @return PromiseInterface<iterable>
     */
    public function findByCreatedAt(DateTimeInterface $dateFrom, DateTimeInterface $dateTo): PromiseInterface
    {
        $statementSelect = <<<SQL
            SELECT DISTINCT
                hp.`hardware_name`,
                hp.`seller_name`,
                hp.`price_amount`,
                hp.`currency_label`
            FROM
                `{$this->_tableNamePurified}` hp
            WHERE
                hp.`created_at` BETWEEN ? AND ?
            ;
SQL;

        $dateFromString = $dateFrom->format('Y-m-d H:i:s');
        $dateToString   = $dateTo->format('Y-m-d H:i:s');

        $priceListPromise = $this->databaseConnection
            // selecting records from the local storage.
            ->query($statementSelect, [$dateFromString, $dateToString])
            // hydrating, to make a DTO set, based on the given raw data.
            ->then(fn (QueryResult $queryResult) => $this->hydrate($queryResult))
        ;

        $priceListPromise = $priceListPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException(
                    'Unable to find price records in the local storage (price repository).',
                    0,
                    $rejectionReason
                );
            }
        );

        return $priceListPromise;
    }

    /**
     * Persists a given price record in the application database
     *
     * @param Price $hardwarePrice Price record DTO
     *
     * @return void
     */
    public function save(Price $hardwarePrice): void
    {
        $statementInsert = <<<SQL
            INSERT INTO
                `{$this->_tableNamePurified}` (`hardware_name`, `seller_name`, `price_amount`, `currency_label`)
            VALUES
                (?, ?, ?, ?)
            ;
SQL;

        $hardwareName = $hardwarePrice->getHardwareName();
        $sellerName   = $hardwarePrice->getSellerIdentifier();

        $priceAmount    = $hardwarePrice->getAmount();
        $pricePrecision = $hardwarePrice->getPrecision();

        if ($pricePrecision > 0) {
            $amountDenormalized = substr_replace($priceAmount, '.', -$pricePrecision, 0);
        } else {
            $amountDenormalized = $priceAmount;
        }

        $currencyLabel = $hardwarePrice->getCurrency();

        // todo: handle promise
        $this->databaseConnection->query(
            $statementInsert,
            [
                $hardwareName,
                $sellerName,
                $amountDenormalized,
                $currencyLabel,
            ]
        );
    }

    /**
     * Transforms a raw dataset from the database driver to a list of application-level DTOs
     *
     * @param QueryResult $queryResult A set of raw data arrays from the async database driver
     *
     * @return Traversable<PriceInterface>|PriceInterface[]
     */
    private function hydrate(QueryResult $queryResult): iterable
    {
        foreach ($queryResult->resultRows as $resultRow) {
            $hardwarePrice = new Price();
            $hardwarePrice->setHardwareName($resultRow['hardware_name']);
            $hardwarePrice->setSellerIdentifier($resultRow['seller_name']);
            $hardwarePrice->setAmount((int) $resultRow['price_amount']);
            // todo: extract a real precision from the row value
            $hardwarePrice->setPrecision(0);
            $hardwarePrice->setCurrency($resultRow['currency_label']);

            yield $hardwarePrice;
        }
    }
}
