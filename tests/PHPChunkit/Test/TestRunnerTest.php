<?php

namespace PHPChunkit\Test;

use PHPChunkit\Configuration;
use PHPChunkit\TestRunner;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class TestRunnerTest extends BaseTest
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Process
     */
    private $process;

    /**
     * @var string
     */
    private $configuration;

    /**
     * @var TestRunner
     */
    private $testRunner;

    protected function setUp()
    {
        $this->app = $this->createMock(Application::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
        $this->process = $this->createMock(Process::class);
        $this->configuration = (new Configuration())
            ->setRootDir(realpath(__DIR__.'/../../..'))
            ->setPhpunitPath(realpath(__DIR__.'/../../../vendor/bin/phpunit'))
        ;

        $this->testRunner = new TestRunnerStub(
            $this->app,
            $this->input,
            $this->output,
            $this->configuration
        );
        $this->testRunner->process = $this->process;
    }

    public function testGetChangedFiles()
    {
        $this->process->expects($this->once())
            ->method('run');

        $files = [
            'src/PHPChunkit/All.php',
            'src/PHPChunkit/BuildSandbox.php',
        ];

        $this->process->expects($this->once())
            ->method('getOutput')
            ->will($this->returnValue(implode("\n", $files)));

        $this->assertEquals($files, $this->testRunner->getChangedFiles());
    }

    public function testGetFilteredFiles()
    {
        $files = [
            'tests/PHPChunkit/Test/Command/AllTest.php',
        ];

        $this->assertEquals($files, $this->testRunner->getFilteredFiles('AllTest.php'));
    }

    public function testGeneratePhpunitXml()
    {
        $files = [
            'tests/PHPChunkit/Test/Command/AllTest.php',
            'tests/PHPChunkit/Test/TestCounterTest.php',
        ];

        $path = $this->testRunner->generatePhpunitXml($files);

        $xmlSource = file_get_contents($path);

        $xml = simplexml_load_string($xmlSource);

        $suite = $xml->testsuites[0]->testsuite;
        $suiteFiles = (array) $suite->file;

        $expectedFiles = [
            $this->configuration->getRootDir().'/tests/PHPChunkit/Test/Command/AllTest.php',
            $this->configuration->getRootDir().'/tests/PHPChunkit/Test/TestCounterTest.php',
        ];

        $this->assertEquals($expectedFiles, $suiteFiles);
    }

    public function testRunChangedFiles()
    {
        $changedFiles = [
            'tests/PHPChunkit/Test/AllTest.php',
            'src/PHPChunkit/All.php',
        ];

        $testRunner = $this->buildPartialMock(
            TestRunnerStub::class,
            [
                'getChangedFiles',
                'generatePhpunitXml',
                'runPhpunit',
            ],
            [
                $this->app,
                $this->input,
                $this->output,
                $this->configuration,
            ]
        );

        $testRunner->expects($this->once())
            ->method('getChangedFiles')
            ->will($this->returnValue($changedFiles));

        $testRunner->expects($this->once())
            ->method('generatePhpunitXml')
            ->will($this->returnValue('/path/to/phpunit.xml'));

        $testRunner->expects($this->once())
            ->method('runPhpunit')
            ->with("-c '/path/to/phpunit.xml'")
            ->will($this->returnValue(0));

        $this->assertEquals(0, $testRunner->runChangedFiles());
    }

    public function testRunFilteredFiles()
    {
        $filteredFiles = [
            'tests/PHPChunkit/Test/AllTest.php',
        ];

        $testRunner = $this->buildPartialMock(
            TestRunnerStub::class,
            [
                'getFilteredFiles',
                'generatePhpunitXml',
                'runPhpunit',
            ],
            [
                $this->app,
                $this->input,
                $this->output,
                $this->configuration,
            ]
        );

        $testRunner->expects($this->once())
            ->method('getFilteredFiles')
            ->with('AllTest')
            ->will($this->returnValue($filteredFiles));

        $testRunner->expects($this->once())
            ->method('generatePhpunitXml')
            ->will($this->returnValue('/path/to/phpunit.xml'));

        $testRunner->expects($this->once())
            ->method('runPhpunit')
            ->with("-c '/path/to/phpunit.xml'")
            ->will($this->returnValue(0));

        $this->assertEquals(0, $testRunner->runFilteredFiles('AllTest'));
    }

    public function testRunTestFiles()
    {
        $files = [
            'tests/PHPChunkit/Test/AllTest.php',
            'src/PHPChunkit/All.php',
        ];

        $testRunner = $this->buildPartialMock(
            TestRunnerStub::class,
            [
                'generatePhpunitXml',
                'runPhpunit',
            ],
            [
                $this->app,
                $this->input,
                $this->output,
                $this->configuration,
            ]
        );

        $testRunner->expects($this->once())
            ->method('generatePhpunitXml')
            ->will($this->returnValue('/path/to/phpunit.xml'));

        $testRunner->expects($this->once())
            ->method('runPhpunit')
            ->with("-c '/path/to/phpunit.xml'")
            ->will($this->returnValue(0));

        $this->assertEquals(0, $testRunner->runTestFiles($files));
    }

    public function testRunPhpunit()
    {
        $testRunner = $this->buildPartialMock(
            TestRunnerStub::class,
            [
                'run',
            ],
            [
                $this->app,
                $this->input,
                $this->output,
                $this->configuration,
            ]
        );

        $testRunner->expects($this->once())
            ->method('run')
            ->with(sprintf("php %s --exclude-group=functional -d memory_limit=''", $this->configuration->getPhpunitPath()))
            ->will($this->returnValue(0));

        $this->assertEquals(0, $testRunner->runPhpunit('--exclude-group=functional'));
    }

    public function testRun()
    {
        $this->testRunner->passthruResponse = 0;
        $this->assertEquals(0, $this->testRunner->run('ls -la'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage The command did not exit successfully.
     */
    public function testRunThrow()
    {
        $this->process->expects($this->once())
            ->method('getExitCode')
            ->willReturn(1);

        $this->assertEquals(0, $this->testRunner->run('ls -la'));
    }

    public function testRunTestCommand()
    {
        $command = $this->createMock(Command::class);

        $this->app->expects($this->once())
            ->method('find')
            ->with('test')
            ->willReturn($command);

        $command->expects($this->once())
            ->method('run')
            ->with(new ArrayInput(['command' => 'test', 'test' => true]))
            ->willReturn(0);

        $this->assertEquals(0, $this->testRunner->runTestCommand('test', ['test' => true]));
    }

    public function testFlags()
    {
        $this->input->expects($this->at(0))
            ->method('getOption')
            ->with('memory-limit')
            ->will($this->returnValue('256M'));

        $this->input->expects($this->at(1))
            ->method('getOption')
            ->with('stop')
            ->will($this->returnValue(true));

        $this->input->expects($this->at(2))
            ->method('getOption')
            ->with('debug')
            ->will($this->returnValue(true));

        $this->output->expects($this->once())
            ->method('getVerbosity')
            ->will($this->returnValue(Output::VERBOSITY_DEBUG));

        $this->output->expects($this->once())
            ->method('isDecorated')
            ->will($this->returnValue(true));

        $expectedFlags = sprintf("-d memory_limit='256M' --stop-on-failure --stop-on-error --verbose --debug --colors", $this->configuration->getRootDir());

        $this->assertEquals($expectedFlags, $this->testRunner->flags());
    }
}

class TestRunnerStub extends TestRunner
{
    /**
     * @var Process
     */
    public $process;

    /**
     * @param string $command
     *
     * @return Process
     */
    protected function createProcess($command)
    {
        return $this->process;
    }
}
