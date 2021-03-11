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

namespace Sterlett\Bridge\Doctrine\Migrations\Provider;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Provider\SchemaProvider as SchemaProviderInterface;

/**
 * Defines the actual database schema state, to automate diffing without ORM layer
 */
class ActualSchemaProvider implements SchemaProviderInterface
{
    /**
     * Name for the table with hardware price records
     *
     * @var string
     */
    private string $tablePriceCpuName;

    /**
     * Name for the table with PassMark rating values
     *
     * @var string
     */
    private string $tableBenchmarkPassMarkName;

    /**
     * Name for the table with V/B ratio values
     *
     * @var string
     */
    private string $tableRatioName;

    /**
     * ActualSchemaProvider constructor.
     *
     * @param string $tablePriceCpuName          Name for the table with hardware price records (CPU category)
     * @param string $tableBenchmarkPassMarkName Name for the table with PassMark rating values
     * @param string $tableRatioName             Name for the table with V/B ratio values
     */
    public function __construct(string $tablePriceCpuName, string $tableBenchmarkPassMarkName, string $tableRatioName)
    {
        $this->tablePriceCpuName          = $tablePriceCpuName;
        $this->tableBenchmarkPassMarkName = $tableBenchmarkPassMarkName;
        $this->tableRatioName             = $tableRatioName;
    }

    /**
     * {@inheritDoc}
     */
    public function createSchema(): Schema
    {
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setDefaultTableOptions(
            [
                'charset' => 'utf8mb4',
                'collate' => 'utf8mb4_unicode_ci',
            ]
        );

        $schema = new Schema([], [], $schemaConfig);

        // hardware prices.
        $priceCpuTable = $schema->createTable($this->tablePriceCpuName);
        $priceCpuTable->addColumn(
            'id',
            Types::INTEGER,
            [
                'notnull'       => true,
                'autoincrement' => true,
                'comment'       => 'Identifier for the price record',
            ]
        );
        $priceCpuTable->addColumn(
            'hardware_name',
            Types::STRING,
            [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Hardware item name, e.g. Ryzen 9',
            ]
        );
        $priceCpuTable->addColumn(
            'hardware_image_uri',
            Types::STRING,
            [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Hardware image URI',
            ]
        );
        $priceCpuTable->addColumn(
            'seller_name',
            Types::STRING,
            [
                'notnull' => true,
                'length'  => 255,
                'comment' => "Seller's company identifier, e.g. citilink",
            ]
        );
        $priceCpuTable->addColumn(
            'price_amount',
            Types::DECIMAL,
            [
                'notnull'   => true,
                'precision' => 13,
                'scale'     => 4,
                'comment'   => 'Price amount, which seller wants for a single item, e.g. 17850.3511',
            ]
        );
        $priceCpuTable->addColumn(
            'currency_label',
            Types::STRING,
            [
                'notnull' => true,
                'fixed'   => true,
                'length'  => 3,
                'comment' => 'Currency label, e.g. RUB',
            ]
        );
        $priceCpuTable->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE,
            [
                'notnull' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Date and time when the price record was found ',
            ]
        );

        $priceCpuTable->setPrimaryKey(['id']);
        $priceCpuTable->setComment('Contains price records for the hardware items');

        $indexCreatedAtName = $this->tablePriceCpuName . '_created_at_ix';
        $priceCpuTable->addIndex(['created_at'], $indexCreatedAtName);

        // hardware benchmarks.
        $benchmarkPassMarkTable = $schema->createTable($this->tableBenchmarkPassMarkName);
        $benchmarkPassMarkTable->addColumn(
            'id',
            Types::INTEGER,
            [
                'notnull'       => true,
                'autoincrement' => true,
                'comment'       => 'Identifier for the benchmark record',
            ]
        );
        $benchmarkPassMarkTable->addColumn(
            'hardware_name',
            Types::STRING,
            [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Hardware item name, e.g. Ryzen 9',
            ]
        );
        $benchmarkPassMarkTable->addColumn(
            'value',
            Types::DECIMAL,
            [
                'notnull'   => true,
                'precision' => 13,
                'scale'     => 2,
                'comment'   => 'Benchmark rating value, e.g. 17850.35',
            ]
        );
        $benchmarkPassMarkTable->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE,
            [
                'notnull' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Date and time when the benchmark record was found ',
            ]
        );

        $benchmarkPassMarkTable->setPrimaryKey(['id']);
        $benchmarkPassMarkTable->setComment('Hardware benchmark record (PassMark)');

        // hardware V/B (Price/Performance) ratio.
        $ratioTable = $schema->createTable($this->tableRatioName);
        $ratioTable->addColumn(
            'id',
            Types::INTEGER,
            [
                'notnull'       => true,
                'autoincrement' => true,
                'comment'       => 'Identifier for the Price/Performance calculation record',
            ]
        );
        $ratioTable->addColumn(
            'hardware_name',
            Types::STRING,
            [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Hardware item name, e.g. Ryzen 9',
            ]
        );
        $ratioTable->addColumn(
            'value',
            Types::DECIMAL,
            [
                'notnull'   => true,
                'precision' => 13,
                'scale'     => 2,
                'comment'   => 'Value for the Price/Performance ratio record, e.g. 17850.35',
            ]
        );
        $ratioTable->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE,
            [
                'notnull' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Date and time when the ratio record has been calculated ',
            ]
        );

        $ratioTable->setPrimaryKey(['id']);
        $ratioTable->setComment('V/B (Price/Performance) ratio record');

        return $schema;
    }
}
