<?php

namespace PHPChunkit\Command;

use PHPChunkit\TestRunner;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Runs all tests in this order: unit and functional.
 */
class All
{
    /**
     * @var TestRunner
     */
    private $testRunner;

    /**
     * @param TestRunner $testRunner
     */
    public function __construct(TestRunner $testRunner)
    {
        $this->testRunner = $testRunner;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $stop = $input->getOption('stop');
        $codes = [];

        // unit
        $codes[] = $code = $this->testRunner->runTestCommand('unit', [
            '--debug' => $input->getOption('debug'),
            '--memory-limit' => $input->getOption('memory-limit'),
            '--stop' => $input->getOption('stop'),
            '--failed' => $input->getOption('failed'),
        ]);

        if ($code && $stop) {
            return $code;
        }

        // functional
        $codes[] = $code = $this->testRunner->runTestCommand('functional', [
            '--debug' => $input->getOption('debug'),
            '--memory-limit' => $input->getOption('memory-limit'),
            '--stop' => $input->getOption('stop'),
            '--create-dbs' => $input->getOption('create-dbs'),
            '--sandbox' => $input->getOption('sandbox'),
            '--failed' => $input->getOption('failed'),
        ]);

        if ($code && $stop) {
            return $code;
        }

        return array_sum($codes) ? 1 : 0;
    }
}
