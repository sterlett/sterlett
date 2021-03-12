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

namespace Sterlett\Hardware\Benchmark;

use InvalidArgumentException;
use React\MySQL\ConnectionInterface;
use Sterlett\Dto\Hardware\Benchmark;

/**
 * A storage with benchmark records for the hardware items
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
     * Table name from which hardware benchmark records will be loaded
     *
     * @var string
     */
    private string $_tableNamePurified;

    /**
     * Repository constructor.
     *
     * @param ConnectionInterface $databaseConnection Manages database connection state and sends async queries
     * @param string              $benchmarkTableName Table name from which hardware benchmark records will be loaded
     */
    public function __construct(ConnectionInterface $databaseConnection, string $benchmarkTableName)
    {
        $this->databaseConnection = $databaseConnection;

        if (1 !== preg_match('/^[a-z0-9_]+$/', $benchmarkTableName)) {
            throw new InvalidArgumentException('Invalid benchmark table name (repository).');
        }

        $this->_tableNamePurified = $benchmarkTableName;
    }

    /**
     * Persists a given benchmark record in the application database
     *
     * @param Benchmark $benchmark Benchmark record DTO
     *
     * @return void
     */
    public function save(Benchmark $benchmark): void
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

        $hardwareName   = $benchmark->getHardwareName();
        $benchmarkValue = $benchmark->getValue();

        // todo: handle promise
        $this->databaseConnection->query($statementInsert, [$hardwareName, $benchmarkValue]);
    }
}
