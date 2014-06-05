<?php

namespace Drupal\Core\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Output\NullOutput;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;

use Drupal\Core\Test\Bootstrapper;
use Drupal\Core\ProxiedCommand\PHPUnit\OptionsFactory;

/**
 * Run Drupal Tests
 */
class RunTestsCommand extends BaseCommand
{
    private $proxiedCommand;
    private $optionsFactory;
    private $drupalRoot;
    private $defaultTestUri = 'http://localhost:80';

    public function __construct($name = null, $drupalRoot = null)
    {
        $this->optionsFactory = new OptionsFactory();
        $this->drupalRoot = $drupalRoot ?: dirname(dirname(dirname(dirname(dirname(__DIR__)))));

        parent::__construct($name);
    }

    protected function configure()
    {
        $proxiedCommand = new \PHPUnit_TextUI_Command();

        $this
            ->setName('tests:run')
            ->setDescription('Run Drupal tests from the shell.')
            ->addArgument('tests', InputArgument::OPTIONAL, 'Path to a test file or directory containing tests.')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Display all available test groups.')
            ->addOption('module', 'm', InputOption::VALUE_REQUIRED, 'Run all tests belonging to the specified module name. (e.g., "node)')
            ->addOption('url', 'u', InputOption::VALUE_REQUIRED, 'The base URL of the root directory of this Drupal checkout; e.g.:
                http://drupal.test/
              Required unless the Drupal root directory maps exactly to:
                http://localhost:80/
              Use a https:// URL to force all tests to be run under SSL.')
            ->setProxiedCommand($proxiedCommand)
            ->setProxiedCommandOptions($this->optionsFactory->createOptions())
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optionUrl = $input->getOption('url') ?: $this->defaultTestUri;

        $this->bootstrapper = new Bootstrapper($this->drupalRoot, $optionUrl);
        $kernel = $this->bootstrapper->bootstrap();

        if ($input instanceof ArrayInput) {
            $input = new StringInput((string)$input);
        }

        $reflectionInput = new \ReflectionClass('Symfony\\Component\\Console\\Input\\ArgvInput');

        $tokens = $reflectionInput->getProperty("tokens");
        $tokens->setAccessible(true);

        $argv = $this->optionsFactory->normalizeArgv($tokens->getValue($input));

        // Remove the command name from the arguments to clean them up for phpunit
        if (false !== ($index = array_search($this->getName(), $argv))) {
            unset($argv[$index]);
        }

        if (count($argv) > 1) {
            ob_start();

            $this->proxiedCommand->run($argv, false);

            $output->write(ob_get_contents(), true, OutputInterface::OUTPUT_RAW);

            ob_end_clean();
        } else {
            $output->write('no tests run!', true, OutputInterface::OUTPUT_RAW);
        }
    }

    protected function setProxiedCommand(\PHPUnit_TextUI_Command $proxiedCommand)
    {
        $this->proxiedCommand = $proxiedCommand;

        return $this;
    }

    protected function setProxiedCommandOptions(array $options = [])
    {
        foreach ($options as $option) {
            $this->addOption(
                $option['name'],
                $option['shortcut'],
                $option['mode'],
                $option['description']
            );
        }

        return $this;
    }
}
