<?php

namespace Heyday\Component\Beam\Command;

use Heyday\Component\Beam\Config\JsonConfigLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

abstract class Command extends BaseCommand
{
    /**
     * @var
     */
    protected $jsonConfigLoader;
    /**
     * @return JsonConfigLoader
     */
    protected function getJsonConfigLoader()
    {
        if (null === $this->jsonConfigLoader) {
            $path = getcwd();
            $paths = array();

            while ($path !== end($paths)) {
                $paths[] = $path;
                $path = dirname($path);
            }

            $this->jsonConfigLoader = new JsonConfigLoader(
                new FileLocator(
                    $paths
                )
            );
        }

        return $this->jsonConfigLoader;
    }
    /**
     * @param  InputInterface $input
     * @return mixed
     */
    protected function getConfig(InputInterface $input)
    {
        return $this->getJsonConfigLoader()->load(
            $input->getOption('config-file')
        );
    }
    /**
     * @param $configFile
     * @return string
     */
    protected function getSrcDir(InputInterface $input)
    {
        return dirname(
            $this->getJsonConfigLoader()->locate(
                $input->getOption('config-file')
            )
        );
    }
    /**
     * @return $this
     */
    protected function addConfigOption()
    {
        $this->addOption(
            'config-file',
            '',
            InputOption::VALUE_REQUIRED,
            'The config file name',
            'beam.json'
        );

        return $this;
    }
}
