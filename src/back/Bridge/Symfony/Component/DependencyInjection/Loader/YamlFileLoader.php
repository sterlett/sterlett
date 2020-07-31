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

namespace Sterlett\Bridge\Symfony\Component\DependencyInjection\Loader;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader as BaseYamlFileLoader;

/**
 * Loads service definitions from YAML files and takes into account global "_defaults" configuration for all services.
 */
class YamlFileLoader extends BaseYamlFileLoader
{
    /**
     * Points to the YAML file where "_defaults" node are considered as a global defaults for all services
     *
     * @var string
     */
    private string $defaultsFilePath;

    /**
     * Parsed "_defaults" node for services as array
     *
     * @var array|null
     */
    private ?array $_defaultsParsed;

    /**
     * {@inheritDoc}
     *
     * @param string $defaultsFilePath Points to the YAML file with "_defaults" node at "services" level
     */
    public function __construct(ContainerBuilder $container, FileLocatorInterface $locator, string $defaultsFilePath)
    {
        parent::__construct($container, $locator);

        $this->defaultsFilePath = $defaultsFilePath;
        $this->_defaultsParsed  = null;
    }

    /**
     * {@inheritDoc}
     */
    protected function loadFile($file)
    {
        $content = parent::loadFile($file);

        // ignoring file path with global defaults to prevent circular reference
        if ($file === $this->defaultsFilePath) {
            return $content;
        }

        if (!is_array($content) || !array_key_exists('services', $content)) {
            return $content;
        }

        $serviceDefinitions = $content['services'];

        if (array_key_exists('_defaults', $serviceDefinitions)) {
            return $content;
        }

        $defaultsParsed = $this->parseDefaults();

        if (null === $defaultsParsed) {
            return $content;
        }

        $content['services']['_defaults'] = $defaultsParsed;

        return $content;
    }

    /**
     * Returns content of "_defaults" node considered as a global service defaults
     *
     * @return array|null
     */
    private function parseDefaults(): ?array
    {
        if (null === $this->_defaultsParsed) {
            $content = $this->loadFile($this->defaultsFilePath);

            $this->_defaultsParsed = $content['services']['_defaults'] ?? [];
        }

        return $this->_defaultsParsed;
    }
}
