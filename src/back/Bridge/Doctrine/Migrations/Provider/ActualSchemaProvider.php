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
    private string $priceTableName;

    /**
     * ActualSchemaProvider constructor.
     *
     * @param string $priceTableName Name for the table with hardware price records
     */
    public function __construct(string $priceTableName)
    {
        $this->priceTableName = $priceTableName;
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
        $priceTable = $schema->createTable($this->priceTableName);
        $priceTable->addColumn(
            'id',
            Types::INTEGER,
            [
                'notnull'       => true,
                'autoincrement' => true,
                'comment'       => 'Identifier for the price record',
            ]
        );
        $priceTable->addColumn(
            'hardware_name',
            Types::STRING,
            [
                'notnull' => true,
                'length'  => 255,
                'comment' => 'Hardware item name, e.g. Ryzen 9',
            ]
        );
        $priceTable->addColumn(
            'seller_name',
            Types::STRING,
            [
                'notnull' => true,
                'length'  => 255,
                'comment' => "Seller's company identifier, e.g. citilink",
            ]
        );
        $priceTable->addColumn(
            'price_amount',
            Types::DECIMAL,
            [
                'notnull'   => true,
                'precision' => 13,
                'scale'     => 4,
                'comment'   => 'Price amount, which seller wants for a single item, e.g. 17850.3511',
            ]
        );
        $priceTable->addColumn(
            'currency_label',
            Types::STRING,
            [
                'notnull' => true,
                'fixed'   => true,
                'length'  => 3,
                'comment' => 'Currency label, e.g. RUB',
            ]
        );
        $priceTable->addColumn(
            'created_at',
            Types::DATETIME_IMMUTABLE,
            [
                'notnull' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => 'Date and time when the price record was found ',
            ]
        );

        $priceTable->setPrimaryKey(['id']);
        $priceTable->setComment('Contains price records for the hardware items');

        $indexCreatedAtName = $this->priceTableName . '_created_at_ix';
        $priceTable->addIndex(['created_at'], $indexCreatedAtName);

        return $schema;
    }
}
