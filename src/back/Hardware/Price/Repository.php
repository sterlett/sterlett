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

use DateTimeInterface;
use InvalidArgumentException;
use React\MySQL\ConnectionInterface;
use React\Promise\PromiseInterface;
use Sterlett\Dto\Hardware\Price;
use Sterlett\Repository\Query\FindBy\CreatedAtQuery;

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
     * Encapsulates a query logic to find data records by 'created_at' field
     *
     * @var CreatedAtQuery
     */
    private CreatedAtQuery $createdAtQuery;

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
     * @param CreatedAtQuery      $createdAtQuery     Encapsulates a query logic to find data records by 'created_at'
     * @param string              $priceTableName     Table name from which hardware price records will be loaded
     */
    public function __construct(
        ConnectionInterface $databaseConnection,
        CreatedAtQuery $createdAtQuery,
        string $priceTableName
    ) {
        $this->databaseConnection = $databaseConnection;
        $this->createdAtQuery     = $createdAtQuery;

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
        return $this->createdAtQuery->executeWithMaxDate($this->_tableNamePurified);
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
        return $this->createdAtQuery->execute($this->_tableNamePurified, $dateFrom, $dateTo);
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
                `{$this->_tableNamePurified}` (
                    `hardware_name`, 
                    `hardware_image_uri`,
                    `seller_name`,
                    `price_amount`,
                    `currency_label`
                )
            VALUES
                (?, ?, ?, ?, ?)
            ;
SQL;

        $hardwareName     = $hardwarePrice->getHardwareName();
        $hardwareImageUri = $hardwarePrice->getHardwareImage();
        $sellerName       = $hardwarePrice->getSellerIdentifier();

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
                $hardwareImageUri,
                $sellerName,
                $amountDenormalized,
                $currencyLabel,
            ]
        );
    }
}
