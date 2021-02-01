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

use InvalidArgumentException;
use React\MySQL\ConnectionInterface;
use Sterlett\Dto\Hardware\Price;

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

    public function findAll(): iterable
    {
        // todo

        return [];
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
        $insertStatement = <<<SQL
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
            $insertStatement,
            [
                $hardwareName,
                $sellerName,
                $amountDenormalized,
                $currencyLabel,
            ]
        );
    }
}
