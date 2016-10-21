<?php

namespace PHPChunkit;

use PHPChunkit\Configuration;
use PHPChunkit\TesterApplication;
use Symfony\Component\Console\Application;

class PHPChunkit
{
    /**
     * @var string
     */
    private $rootDir;

    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function getApplication(Configuration $configuration)
    {
        return new TesterApplication($this->createSymfonyConsoleApplication(), $configuration);
    }

    public function getConfiguration()
    {
        $configuration = $this->loadConfiguration();

        $configuration->throwExceptionIfConfigurationIncomplete();

        return $configuration;
    }

    private function createSymfonyConsoleApplication()
    {
        return new Application();
    }

    private function loadConfiguration()
    {
        $configuration = $this->createConfiguration();

        $this->loadPHPChunkitBootstrap($configuration);

        return $configuration;
    }

    private function createConfiguration()
    {
        $xmlPath = $this->findPHPChunkitXmlPath();

        $configuration = $xmlPath
            ? Configuration::createFromXmlFile($xmlPath)
            : new Configuration()
        ;

        $configuration->setSandboxEnabled($this->isSandboxEnabled());

        if (!$configuration->getRootDir()) {
            $configuration->setRootDir($this->rootDir);
        }

        return $configuration;
    }

    private function isSandboxEnabled()
    {
        return array_filter($_SERVER['argv'], function ($arg) {
            return strpos($arg, 'sandbox') !== false;
        }) ? true : false;
    }

    /**
     * @return null|string
     */
    private function findPHPChunkitXmlPath()
    {
        if (file_exists($distXmlPath = $this->rootDir.'/phpchunkit.xml.dist')) {
            return $distXmlPath;
        }

        if (file_exists($defaultXmlPath = $this->rootDir.'/phpchunkit.xml')) {
            return $defaultXmlPath;
        }
    }

    private function loadPHPChunkitBootstrap(Configuration $configuration)
    {
        if ($bootstrapPath = $configuration->getBootstrapPath()) {
            if (!file_exists($bootstrapPath)) {
                throw new \InvalidArgumentException(
                    sprintf('Bootstrap path "%s" does not exist.', $bootstrapPath)
                );
            }

            require_once $bootstrapPath;
        }
    }
}
