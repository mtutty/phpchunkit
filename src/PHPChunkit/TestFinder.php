<?php

namespace PHPChunkit;

class TestFinder
{
    /**
     * @var string
     */
    private $testsDirectory;

    /**
     * @param string $testsDirectory
     */
    public function __construct($testsDirectory)
    {
        $this->testsDirectory = $testsDirectory;
    }

    public function findFunctionalTestFiles()
    {
        $command = sprintf(
            'find %s -name *Test.php -print0 | xargs -0 grep -l "@group functional" | sort',
            $this->testsDirectory
        );
        $output = shell_exec($command);

        return explode("\n", trim($output));
    }
}
