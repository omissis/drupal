<?php

namespace Drupal\Core\Tests\Command;

use Drupal\Core\Command\RunTestsCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;

class RunTestsCommandTest extends \PHPUnit_Framework_TestCase
{
    private static $fixturesDir;
    private static $consoleScriptPath;

    public static function setUpBeforeClass()
    {
        self::$fixturesDir = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Fixture');
        self::$consoleScriptPath = realpath(dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'console');
    }

    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    /**
     * Test a default command run.
     *
     * @todo this should be changed when a proper default command behaviour is identified
     */
    public function testNoArgumentsRun()
    {
        $process = new Process('php ' . self::$consoleScriptPath . ' tests:run');
        $process->start();

        $process->wait(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->fail($buffer);
            }
        });

        $this->assertSame('no tests run!', trim($process->getOutput()));
    }

    /**
     * Test a command run when a path to a class is specified.
     */
    public function testFilePathArgumentRun()
    {
        $process = new Process('php ' . self::$consoleScriptPath . ' tests:run ' . self::$fixturesDir . DIRECTORY_SEPARATOR . 'Tests' . DIRECTORY_SEPARATOR . 'FooTest.php');
        $process->start();

        $process->wait(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->fail($buffer);
            }
        });

        $output = trim($process->getOutput());

        $this->assertRegexp('/OK\ \(1\ test\,\ 1\ assertion\)/', $output);
        $this->assertNotRegexp('/no\ tests\ run\!/', $output);
    }

    /**
     * Test a command run when a path to a fo,der is specified.
     */
    public function testDirectoryPathArgumentRun()
    {
        $process = new Process('php ' . self::$consoleScriptPath . ' tests:run ' . self::$fixturesDir . DIRECTORY_SEPARATOR . 'Tests');
        $process->start();

        $process->wait(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->fail($buffer);
            }
        });

        $output = trim($process->getOutput());

        $this->assertRegexp('/OK\ \(2\ tests\,\ 2\ assertions\)/', $output);
        $this->assertNotRegexp('/no\ tests\ run\!/', $output);
    }

    /**
     * Test a command run when a path to a fo,der is specified.
     */
    public function testPHPUnitVersionRun()
    {
        $process = new Process('php ' . self::$consoleScriptPath . ' tests:run --phpunit-version');
        $process->start();

        $process->wait(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->fail($buffer);
            }
        });

        $output = trim($process->getOutput());

        $this->assertRegexp('/PHPUnit\ \d+\.\d+\.\d+\ by\ Sebastian\ Bergmann\./', $output);
    }
}
