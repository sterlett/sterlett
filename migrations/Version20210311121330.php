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
 * Renaming a price table for CPUs, creating a table for benchmarks (PassMark) and ratio calculations
 */
final class Version20210311121330 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql('RENAME TABLE hardware_price TO hardware_price_cpu');
        $this->addSql("
            ALTER TABLE
                hardware_price_cpu
            RENAME INDEX
                hardware_price_created_at_ix
            TO
                hardware_price_cpu_created_at_ix
        ");

        $this->addSql("
            CREATE TABLE hardware_benchmark_passmark (
                id INT AUTO_INCREMENT NOT NULL COMMENT 'Identifier for the benchmark record',
                hardware_name VARCHAR(255) NOT NULL COMMENT 'Hardware item name, e.g. Ryzen 9',
                value NUMERIC(13, 2) NOT NULL COMMENT 'Benchmark rating value, e.g. 17850.35',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Date and time when the benchmark record was found (DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` 
            ENGINE = InnoDB
            COMMENT = 'Hardware benchmark record (PassMark)'
        ");

        $this->addSql("
            CREATE TABLE hardware_ratio (
                id INT AUTO_INCREMENT NOT NULL COMMENT 'Identifier for the Price/Performance calculation record',
                hardware_name VARCHAR(255) NOT NULL COMMENT 'Hardware item name, e.g. Ryzen 9',
                value NUMERIC(13, 2) NOT NULL COMMENT 'Value for the Price/Performance ratio record, e.g. 17850.35',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Date and time when the ratio record has been calculated (DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` 
            ENGINE = InnoDB
            COMMENT = 'V/B (Price/Performance) ratio record'
        ");
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE
                hardware_price_cpu
            RENAME INDEX
                hardware_price_cpu_created_at_ix
            TO
                hardware_price_created_at_ix
        ");
        $this->addSql('RENAME TABLE hardware_price_cpu TO hardware_price');

        $this->addSql('DROP TABLE hardware_benchmark_passmark');
        $this->addSql('DROP TABLE hardware_ratio');
    }
}
