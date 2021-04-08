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
 * Creating a lookup index for the benchmark (PassMark) table to speed up the deal analyser service
 */
final class Version20210408182431 extends AbstractMigration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE INDEX hardware_benchmark_passmark_created_at_ix ON hardware_benchmark_passmark (created_at)'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX hardware_benchmark_passmark_created_at_ix ON hardware_benchmark_passmark');
    }
}
