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

namespace SterlettMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creating a table to bind benchmark values with price records by hardware name
 */
final class Version20210312141727 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE hardware_benchmark_hardware_price (
                id INT AUTO_INCREMENT NOT NULL COMMENT 'Identifier for the price-benchmark binding record',
                benchmark_hardware_name VARCHAR(255) NOT NULL COMMENT 'Hardware item name from the benchmark record',
                price_hardware_name VARCHAR(255) NOT NULL COMMENT 'Hardware item name from the price record',
                PRIMARY KEY(id),
                UNIQUE INDEX benchmark_hardware_name_price_hardware_name_uq (
                    benchmark_hardware_name, price_hardware_name
                )
            ) DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` 
            ENGINE = InnoDB 
            COMMENT = 'Price-benchmark binding record'
        ");
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE hardware_benchmark_hardware_price');
    }
}
