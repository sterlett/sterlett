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

namespace Sterlett\Hardware\VBRatio;

use InvalidArgumentException;
use React\MySQL\ConnectionInterface;
use RuntimeException;
use Sterlett\Dto\Hardware\VBRatio;
use Sterlett\Hardware\PriceInterface;

/**
 * A storage with V/B ratio records for the hardware items
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
     * Table name from which V/B ratio records will be loaded
     *
     * @var string
     */
    private string $_tableNamePurified;

    /**
     * Repository constructor.
     *
     * @param ConnectionInterface $databaseConnection Manages database connection state and sends async queries
     * @param string              $ratioTableName     Table name from which V/B ratio records will be loaded
     */
    public function __construct(ConnectionInterface $databaseConnection, string $ratioTableName)
    {
        $this->databaseConnection = $databaseConnection;

        if (1 !== preg_match('/^[a-z0-9_]+$/', $ratioTableName)) {
            throw new InvalidArgumentException('Invalid V/B ratio table name (repository).');
        }

        $this->_tableNamePurified = $ratioTableName;
    }

    /**
     * Persists a given V/B ratio record in the database
     *
     * @param VBRatio $ratio A V/B ratio object
     *
     * @return void
     */
    public function save(VBRatio $ratio): void
    {
        $statementInsert = <<<SQL
            INSERT INTO
                `{$this->_tableNamePurified}` (
                    `hardware_name`, 
                    `value`
                )
            VALUES
                (?, ?)
            ;
SQL;

        $hardwarePrices = $ratio->getSourcePrices();
        $priceSample    = $hardwarePrices[0] ?? null;

        if (!$priceSample instanceof PriceInterface) {
            throw new RuntimeException('Invalid V/B ratio record: no price data.');
        }

        $hardwareName = $priceSample->getHardwareName();
        $ratioValue   = $ratio->getValue();

        // todo: handle promise
        $this->databaseConnection->query($statementInsert, [$hardwareName, $ratioValue]);
    }
}
