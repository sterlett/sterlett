<?php

/*
 * This file is part of the Sterlett project <https://github.com/sterlett/sterlett>.
 *
 * (c) 2020 Pavel Petrov <itnelo@gmail.com>.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license https://opensource.org/licenses/GPL-3.0 GPL-3.0
 */

declare(strict_types=1);

namespace Sterlett\Bridge\Symfony\Component\DependencyInjection\Configurator;

use Exception;
use RuntimeException;
use Sterlett\Bridge\Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Dotenv\Exception\FormatException as EnvFileFormatException;
use Symfony\Component\Dotenv\Exception\PathException as EnvFilePathException;
use Symfony\Component\Yaml\Exception\ParseException as YamlFileParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Configures a container instance according to passed environment variables, parameters and service definitions.
 * Uses Dotenv library to load environment variables and expects YAML format for configuration files,
 * doesn't perform any sort of premature compilation for the container.
 */
class YamlConfigurator
{
    /**
     * Path to file with environment variables
     *
     * @var string|null
     */
    private ?string $environmentFilePath;

    /**
     * Path to file with configuration parameters
     *
     * @var string|null
     */
    private ?string $parameterFilePath;

    /**
     * A set of file paths where service definitions are stored
     *
     * @var iterable|null
     */
    private ?iterable $definitionFilePaths;

    /**
     * Path to file with shared "_defaults" option set for all service definitions (will not override if that node
     * is already exists in the target file, see YamlFileLoader from the "Bridge\Symfony" scope)
     *
     * @var string|null
     *
     * @see YamlFileLoader::loadFile
     */
    private ?string $definitionDefaultsFilePath;

    /**
     * YamlConfigurator constructor.
     */
    public function __construct()
    {
        $this->environmentFilePath        = null;
        $this->parameterFilePath          = null;
        $this->definitionFilePaths        = null;
        $this->definitionDefaultsFilePath = null;
    }

    /**
     * Returns ContainerBuilder instance with applied parameters and service definitions
     *
     * @return ContainerBuilder
     *
     * @throws EnvFilePathException   When a file with environment variables doesn't exist or isn't readable
     * @throws EnvFileFormatException When a file with environment variables has a syntax error
     * @throws YamlFileParseException If the file with configuration parameters couldn't be read or the YAML isn't valid
     * @throws Exception              If an error of any other type has been occurred during container configuration
     */
    public function getContainerBuilder(): ContainerBuilder
    {
        if (is_string($this->environmentFilePath) && file_exists($this->environmentFilePath)) {
            $dotenv = new Dotenv();

            // will populate env variables from env-type files (only if they're not already set).
            $dotenv->loadEnv($this->environmentFilePath);
        }

        $parameters = [];

        if (is_string($this->parameterFilePath)) {
            $configuration = Yaml::parseFile($this->parameterFilePath);
            $parameters    = $configuration['parameters'] ?? [];
        }

        $parameterBag     = new EnvPlaceholderParameterBag($parameters);
        $containerBuilder = new ContainerBuilder($parameterBag);

        if (is_iterable($this->definitionFilePaths)) {
            if (!is_string($this->definitionDefaultsFilePath)) {
                throw new RuntimeException(
                    'Path to file with fallback "_defaults" option set (definitionDefaultsFilePath) ' .
                    'must be explicitly specified alongside service definitions if they have added.'
                );
            }

            // loading service definitions.
            $fileLocator      = new FileLocator();
            $definitionLoader = new YamlFileLoader($containerBuilder, $fileLocator, $this->definitionDefaultsFilePath);

            foreach ($this->definitionFilePaths as $definitionFilePath) {
                $definitionLoader->load($definitionFilePath);
            }
        }

        return $containerBuilder;
    }

    /**
     * Sets path to file with environment variables
     *
     * @param string $environmentFilePath Path to file with environment variables
     *
     * @return void
     */
    public function setEnvironmentFilePath(string $environmentFilePath): void
    {
        $this->environmentFilePath = $environmentFilePath;
    }

    /**
     * Sets path to file with configuration parameters
     *
     * @param string $parameterFilePath Path to file with configuration parameters
     *
     * @return void
     */
    public function setParameterFilePath(string $parameterFilePath): void
    {
        $this->parameterFilePath = $parameterFilePath;
    }

    /**
     * Sets a list of file paths where service definitions are stored
     *
     * @param iterable $definitionFilePaths A list of file paths where service definitions are stored
     *
     * @return void
     */
    public function setDefinitionFilePaths(iterable $definitionFilePaths): void
    {
        $this->definitionFilePaths = $definitionFilePaths;
    }

    /**
     * Sets path to file with shared "_defaults" option set for service definitions
     *
     * @param string $definitionDefaultsFilePath Path to file with shared "_defaults" option set for service definitions
     *
     * @return void
     */
    public function setDefinitionDefaultsFilePath(string $definitionDefaultsFilePath): void
    {
        $this->definitionDefaultsFilePath = $definitionDefaultsFilePath;
    }
}
