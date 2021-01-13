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
 * Creating a table to persist hardware price records
 */
final class Version20210112034139 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE hardware_price (
                id INT AUTO_INCREMENT NOT NULL COMMENT 'Identifier for the price record',
                hardware_name VARCHAR(255) NOT NULL COMMENT 'Hardware item name, e.g. Ryzen 9',
                seller_name VARCHAR(255) NOT NULL COMMENT 'Seller\'s company identifier, e.g. citilink',
                price_amount NUMERIC(13, 4) NOT NULL COMMENT 'Price amount, which seller wants for a single item, e.g. 17850.3511',
                currency_label CHAR(3) NOT NULL COMMENT 'Currency label, e.g. RUB',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL COMMENT 'Date and time when the price record was found (DC2Type:datetime_immutable)',
                PRIMARY KEY(id),
                INDEX hardware_price_created_at_ix (created_at)
            ) DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` 
            ENGINE = InnoDB 
            COMMENT = 'Contains price records for the hardware items'
        ");
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE hardware_price');
    }
}
