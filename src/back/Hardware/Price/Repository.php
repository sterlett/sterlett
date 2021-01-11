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
    private string $priceTableName;

    /**
     * Repository constructor.
     *
     * @param ConnectionInterface $databaseConnection Manages database connection state and sends async queries
     * @param string              $priceTableName     Table name from which hardware price records will be loaded
     */
    public function __construct(ConnectionInterface $databaseConnection, string $priceTableName)
    {
        $this->databaseConnection = $databaseConnection;
        $this->priceTableName     = $priceTableName;
    }

    public function findAll(): iterable
    {
        // todo

        return [];
    }

    public function save(Price $hardwarePrice): void
    {
        // todo
    }
}
