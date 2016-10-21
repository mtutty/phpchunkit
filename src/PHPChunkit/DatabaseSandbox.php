<?php

namespace PHPChunkit;

class DatabaseSandbox
{
    const SANDBOXED_DATABASE_NAME_PATTERN = '%s_%s';

    /**
     * @var bool
     */
    private $sandboxEnabled = true;

    /**
     * @var []
     */
    private $databaseNames = [];

    /**
     * @var []
     */
    private $sandboxDatabaseNames = [];

    /**
     * @param bool $sandboxEnabled
     * @param array $databaseNames
     */
    public function __construct($sandboxEnabled = false, $databaseNames = array())
    {
        $this->sandboxEnabled = $sandboxEnabled;
        $this->databaseNames = $databaseNames;
    }

    public function getSandboxEnabled()
    {
        return $this->sandboxEnabled;
    }

    public function setSandboxEnabled($sandboxEnabled)
    {
        $this->sandboxEnabled = $sandboxEnabled;
    }

    public function getDatabaseNames()
    {
        return $this->databaseNames;
    }

    public function setDatabaseNames(array $databaseNames)
    {
        $this->databaseNames = $databaseNames;
    }

    /**
     * Gets the original test database names.
     *
     * @return []
     */
    public function getTestDatabaseNames()
    {
        $databaseNames = [];

        foreach ($this->databaseNames as $databaseName) {
            $databaseNames[$databaseName] = sprintf(self::SANDBOXED_DATABASE_NAME_PATTERN,
                $databaseName, 'test'
            );
        }

        return $databaseNames;
    }

    /**
     * Gets all the sandboxed database names.
     *
     * @return []
     */
    public function getSandboxedDatabaseNames()
    {
        $this->initialize();

        return $this->sandboxDatabaseNames;
    }

    /**
     * @return string
     */
    protected function generateUniqueId()
    {
        return uniqid();
    }

    /**
     * Initialize database names.
     */
    private function initialize()
    {
        if (!$this->sandboxDatabaseNames) {
            $this->sandboxDatabaseNames = $this->generateDatabaseNames();
        }
    }

    /**
     * Generate sandboxed test database names.
     *
     * @return []
     */
    private function generateDatabaseNames()
    {
        $databaseNames = [];

        foreach ($this->databaseNames as $databaseName) {
            if ($this->sandboxEnabled) {
                $databaseNames[$databaseName] = sprintf(self::SANDBOXED_DATABASE_NAME_PATTERN,
                    $databaseName, $this->generateUniqueId()
                );
            } else {
                $databaseNames[$databaseName] = sprintf(self::SANDBOXED_DATABASE_NAME_PATTERN,
                    $databaseName, 'test'
                );
            }
        }

        return $databaseNames;
    }
}
