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

namespace Sterlett\Repository\Query\FindBy;

use DateTime;
use DateTimeInterface;
use React\MySQL\ConnectionInterface;
use React\MySQL\QueryResult;
use React\Promise\PromiseInterface;
use RuntimeException;
use Sterlett\Repository\HydratorInterface;
use Throwable;

/**
 * Encapsulates a query logic to find data records by 'created_at' for the different reactive repositories
 */
class CreatedAtQuery
{
    /**
     * Manages database connection state and sends async queries
     *
     * @var ConnectionInterface
     */
    private ConnectionInterface $databaseConnection;

    /**
     * Transforms a raw dataset from the database driver to a list of application-level DTOs
     *
     * @var HydratorInterface
     */
    private HydratorInterface $entityHydrator;

    /**
     * CreatedAtQuery constructor.
     *
     * @param ConnectionInterface $databaseConnection Manages database connection state and sends async queries
     * @param HydratorInterface   $entityHydrator     Transforms a raw dataset to a list of application-level DTOs
     */
    public function __construct(
        ConnectionInterface $databaseConnection,
        HydratorInterface $entityHydrator
    ) {
        $this->databaseConnection = $databaseConnection;
        $this->entityHydrator     = $entityHydrator;
    }

    /**
     * Returns a promise that resolves to a couple [iterable, ?DateTimeInterface], where the first element is a
     * collection of records and the second one - the most recent record date or a NULL if there are no records
     *
     * @param string $tableName Table name for records extraction (must be purified)
     *
     * @return PromiseInterface<array>
     */
    public function executeWithMaxDate(string $tableName): PromiseInterface
    {
        $statementSelectCreatedAtMax = <<<SQL
            SELECT
                DATE(MAX(created_at)) AS 'created_at'
            FROM
                `{$tableName}`
            ;
SQL;

        $recordListWithDatePromise = $this->databaseConnection
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
                function (?DateTimeInterface $dateFrom) use ($tableName) {
                    if (!$dateFrom instanceof DateTimeInterface) {
                        return [[], null];
                    }

                    $dateTo = new DateTime();

                    return $this
                        ->execute($tableName, $dateFrom, $dateTo)
                        // enhancing a resolve value to include both the records and the max date available.
                        ->then(fn (iterable $records) => [$records, $dateFrom])
                    ;
                }
            )
        ;

        return $recordListWithDatePromise;
    }

    /**
     * Returns a promise that resolves to an iterable collection of records from the storage.
     *
     * Note: duplicate records will not be included (if any).
     *
     * @param string            $tableName Table name for records extraction (must be purified)
     * @param DateTimeInterface $dateFrom  The lower datetime boundary for desired collection of records
     * @param DateTimeInterface $dateTo    The upper datetime boundary
     *
     * @return PromiseInterface<iterable>
     */
    public function execute(string $tableName, DateTimeInterface $dateFrom, DateTimeInterface $dateTo): PromiseInterface
    {
        $entityFields = $this->entityHydrator->getFieldNames();
        $selectFields = implode(', ', $entityFields);

        $statementSelect = <<<SQL
            SELECT DISTINCT
                {$selectFields}
            FROM
                `{$tableName}` hp
            WHERE
                hp.`created_at` BETWEEN ? AND ?
            ;
SQL;

        $dateFromString = $dateFrom->format('Y-m-d H:i:s');
        $dateToString   = $dateTo->format('Y-m-d H:i:s');

        $recordListPromise = $this->databaseConnection
            // selecting records from the local storage.
            ->query($statementSelect, [$dateFromString, $dateToString])
            // hydrating, to make a DTO set, based on the given raw data.
            ->then(fn (QueryResult $queryResult) => $this->entityHydrator->hydrate($queryResult))
        ;

        $recordListPromise = $recordListPromise->then(
            null,
            function (Throwable $rejectionReason) {
                throw new RuntimeException('Unable to find records in the local storage.', 0, $rejectionReason);
            }
        );

        return $recordListPromise;
    }
}
