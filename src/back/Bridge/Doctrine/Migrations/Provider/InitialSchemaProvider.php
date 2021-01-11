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
use Doctrine\Migrations\Provider\SchemaProvider as SchemaProviderInterface;

/**
 * Defines a base schema state, to automate diffing without ORM layer
 */
class InitialSchemaProvider implements SchemaProviderInterface
{
    /**
     * Name for the table with hardware price records
     *
     * @var string
     */
    private string $priceTableName;

    /**
     * InitialSchemaProvider constructor.
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
        $schema = new Schema();

        $priceTable = $schema->createTable($this->priceTableName);

        // todo

        return $schema;
    }
}
