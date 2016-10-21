<?php

namespace PHPChunkit;

use InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Configuration
{
    /**
     * @var string
     */
    private $rootDir = '';

    /**
     * @var array
     */
    private $watchDirectories = [];

    /**
     * @var string
     */
    private $testsDirectory = '';

    /**
     * @var string
     */
    private $bootstrapPath = '';

    /**
     * @var string
     */
    private $phpunitPath = '';

    /**
     * @var null|EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var null|DatabaseSandbox
     */
    private $databaseSandbox;

    public static function createFromXmlFile($path)
    {
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('XML file count not be found at path "%s"', $path));
        }

        $configuration = new self();

        $xml = simplexml_load_file($path);
        $attributes = $xml->attributes();

        if ($rootDir = (string) $attributes['root-dir']) {
            $configuration->setRootDir($rootDir);
        }

        if ($bootstrapPath = (string) $attributes['bootstrap']) {
            $configuration->setBootstrapPath($bootstrapPath);
        }

        if ($testsDir = (string) $attributes['tests-dir']) {
            $configuration->setTestsDirectory($testsDir);
        }

        if ($phpunitPath = (string) $attributes['phpunit-path']) {
            $configuration->setPhpunitPath($phpunitPath);
        }

        if ($watchDirectories = (array) $xml->{'watch-directories'}->{'watch-directory'}) {
            $configuration->setWatchDirectories($watchDirectories);
        }

        if ($databaseNames = (array) $xml->{'database-names'}->{'database-name'}) {
            $configuration->setDatabaseNames($databaseNames);
        }

        $events = (array) $xml->{'events'};
        $listeners = isset($events['listener']) ? $events['listener'] : null;

        if ($listeners) {
            $eventDispatcher = $configuration->getEventDispatcher();

            foreach ($listeners as $listener) {
                $eventName = (string) $listener->attributes()['event'];
                $className = (string) $listener->class;

                $listener = new $className($configuration);

                if (!$listener instanceof ListenerInterface) {
                    throw new InvalidArgumentException(
                        sprintf('%s does not implement %s', $className, ListenerInterface::class)
                    );
                }

                $eventDispatcher->addListener($eventName, [$listener, 'execute']);
            }
        }

        return $configuration;
    }

    public function setRootDir($rootDir)
    {
        if (!is_dir($rootDir)) {
            throw new \InvalidArgumentException(
                sprintf('Root directory "%s" does not exist.', $rootDir)
            );
        }

        $this->rootDir = realpath($rootDir);

        return $this;
    }

    public function getRootDir()
    {
        return $this->rootDir;
    }

    public function setWatchDirectories(array $watchDirectories)
    {        foreach ($watchDirectories as $key => $watchDirectory) {
            if (!is_dir($watchDirectory)) {
                throw new \InvalidArgumentException(
                    sprintf('Watch directory "%s" does not exist.', $watchDirectory)
                );
            }

            $watchDirectories[$key] = realpath($watchDirectory);
        }

        $this->watchDirectories = $watchDirectories;

        return $this;
    }

    public function getWatchDirectories()
    {
        return $this->watchDirectories;
    }

    public function setTestsDirectory($testsDirectory)
    {
        if (!is_dir($testsDirectory)) {
            throw new \InvalidArgumentException(
                sprintf('Tests directory "%s" does not exist.', $testsDirectory)
            );
        }

        $this->testsDirectory = realpath($testsDirectory);

        return $this;
    }

    public function getTestsDirectory()
    {
        return $this->testsDirectory;
    }

    public function setBootstrapPath($bootstrapPath)
    {
        if (!file_exists($bootstrapPath)) {
            throw new \InvalidArgumentException(
                sprintf('Bootstrap path "%s" does not exist.', $bootstrapPath)
            );
        }

        $this->bootstrapPath = realpath($bootstrapPath);

        return $this;
    }

    public function getBootstrapPath()
    {
        return $this->bootstrapPath;
    }

    public function setPhpunitPath($phpunitPath)
    {
        if (!file_exists($phpunitPath)) {
            throw new \InvalidArgumentException(
                sprintf('PHPUnit path "%s" does not exist.', $bootstrapPath)
            );
        }

        $this->phpunitPath = realpath($phpunitPath);

        return $this;
    }

    public function getPhpunitPath()
    {
        return $this->phpunitPath;
    }

    public function setDatabaseSandbox(DatabaseSandbox $databaseSandbox)
    {
        $this->databaseSandbox = $databaseSandbox;

        return $this;
    }

    public function getDatabaseSandbox()
    {
        if ($this->databaseSandbox === null) {
            $this->databaseSandbox = new DatabaseSandbox();
        }

        return $this->databaseSandbox;
    }

    public function setDatabaseNames(array $databaseNames)
    {
        $this->getDatabaseSandbox()->setDatabaseNames($databaseNames);

        return $this;
    }

    public function setSandboxEnabled($sandboxEnabled)
    {
        $this->getDatabaseSandbox()->setSandboxEnabled($sandboxEnabled);

        return $this;
    }

    public function setEventDispatcher(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    public function getEventDispatcher()
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new EventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function throwExceptionIfConfigurationIncomplete()
    {
        if (!$this->rootDir) {
            throw new \InvalidArgumentException('You must configure a root directory.');
        }

        if (!$this->watchDirectories) {
            throw new \InvalidArgumentException('You must configure a watch directory.');
        }

        if (!$this->testsDirectory) {
            throw new \InvalidArgumentException('You must configure a tests directory.');
        }

        if (!$this->phpunitPath) {
            throw new \InvalidArgumentException('You must configure a phpunit path.');
        }

        return true;
    }
}
