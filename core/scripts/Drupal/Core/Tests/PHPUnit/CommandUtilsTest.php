<?php

namespace Drupal\Core\Tests\PHPUnit;

use Drupal\Core\PHPUnit\CommandUtils;
use Symfony\Component\Console\Input\InputOption;

class BootstrapperTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->command = new \PHPUnit_TextUI_Command();
    }

    public function tearDown()
    {
        $this->command = null;
    }

    /**
     * @dataProvider optionLinesProvider
     */
    public function testOptionLinesParsing($expectedName, $expectedShortcut, $expectedDescription, $expectedMode, $line)
    {
        $option = CommandUtils::createOptionFromHelpLine($line);

        $this->assertSame($expectedName, $option['name']);
        $this->assertSame($expectedShortcut, $option['shortcut']);
        $this->assertSame($expectedDescription, $option['description']);
        $this->assertSame($expectedMode, $option['mode']);
    }

    /**
     * @dataProvider userTextLinesProvider
     */
    public function testUserTextLinesParsing($line)
    {
        $this->assertEmpty(CommandUtils::createOptionFromHelpLine($line));
    }

    public function testHasAllOptions()
    {
        $phpunitCommand = new \ReflectionClass(get_class($this->command));

        $property = $phpunitCommand->getProperty("longOptions");
        $property->setAccessible(true);

        $phpunitOptions = $property->getValue($this->command);

        foreach ($phpunitOptions as $key => $value) {
            unset($phpunitOptions[$key]);

            $phpunitOptions['phpunit-' . rtrim($key, '=')] = $value;
        }

        $options = CommandUtils::createOptionsFromCommand($this->command);

        $diffOne = array_diff_key($phpunitOptions, $options);
        $diffTwo = array_diff_key($options, $phpunitOptions);

        $this->assertEmpty($diffOne, var_export($diffOne, true));
        $this->assertEmpty($diffTwo, var_export($diffTwo, true));
    }

    public function testNormalizeArgv()
    {
        $argv = CommandUtils::normalizeArgv([
            '--phpunit-foo',
            '--phpunit-bar',
            '-pub',
            '-puq',
        ]);

        $this->assertEquals([
            '--foo',
            '--bar',
            '-b',
            '-q',
        ], $argv);
    }

    public function optionLinesProvider()
    {
        return [
            [
                'phpunit-coverage-clover', '', 'Generate code coverage report in Clover XML format.', InputOption::VALUE_REQUIRED,
                '--coverage-clover <file>  Generate code coverage report in Clover XML format.',
            ],
            [
                'phpunit-coverage-text', '', 'Generate code coverage report in text format.', InputOption::VALUE_REQUIRED,
                '--coverage-text=<file>    Generate code coverage report in text format.'
            ],
            [
                'phpunit-exclude-group', '', 'Exclude tests from the specified group(s).', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                '--exclude-group ...       Exclude tests from the specified group(s).'
            ],
            [
                'phpunit-verbose', 'puv', 'Output more verbose information.', InputOption::VALUE_NONE,
                '-v|--verbose              Output more verbose information.'
            ],
            [
                'phpunit-configuration', 'puc', 'Read configuration from XML file.', InputOption::VALUE_REQUIRED,
                '-c|--configuration <file> Read configuration from XML file.'
            ],
            [
                '', 'pud', 'Sets a php.ini value.', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                '-d key[=value]            Sets a php.ini value.'
            ],
        ];
    }

    public function userTextLinesProvider()
    {
        return [
            [
                'PHPUnit 3.7.21 by Sebastian Bergmann.',
            ],
            [
                'Usage: phpunit [switches] UnitTest [UnitTest.php]',
            ],
            [
                'phpunit [switches] <directory>',
            ],
        ];
    }
}
