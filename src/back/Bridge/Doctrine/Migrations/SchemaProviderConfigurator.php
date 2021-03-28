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

namespace Sterlett\Bridge\Doctrine\Migrations;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Provider\SchemaProvider as SchemaProviderInterface;
use Sterlett\Bridge\Doctrine\Migrations\Provider\ActualSchemaProvider;

/**
 * Instantiates and configures custom schema provider for doctrine diff command, to bypass ORM layer
 */
class SchemaProviderConfigurator
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
     * Returns an instance of custom schema provider for the application (to substitute annotation-based way from the
     * ORM scope)
     *
     * @param DependencyFactory $dependencyFactory Manages internal dependencies for doctrine commands
     *
     * @return SchemaProviderInterface
     */
    public function __invoke(DependencyFactory $dependencyFactory): SchemaProviderInterface
    {
        return new ActualSchemaProvider(
            $this->tablePriceCpuName,
            $this->tableBenchmarkPassMarkName,
            $this->tableRatioName
        );
    }
}
