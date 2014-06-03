<?php

namespace Drupal\Core\Tests\Command;

use Drupal\Core\Command\RunTestsCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RunTestsCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $application = new Application();

        $application->add(new RunTestsCommand());

        $this->command = $application->find('tests:run');
        $this->commandTester = new CommandTester($this->command);
    }

    public function tearDown()
    {
        $this->command = null;
        $this->commandTester = null;
    }

    public function testPHPUnitVersion()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName()
        ]);

        $this->assertSame('no tests run!', trim($this->commandTester->getDisplay()));
    }
}
