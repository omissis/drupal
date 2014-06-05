<?php

namespace Drupal\Core\ProxiedCommand\PHPUnit;

use Symfony\Component\Console\Input\InputOption;

/**
 * Extract information from PHPUnit's Command class.
 *
 * @todo refactor this class to separate the factory methods from the transformers ones.
 */
class OptionsFactory
{
    public function __construct($longPrefix = 'phpunit', $shortPrefix = 'pu')
    {
        $this->longPrefix = $longPrefix;
        $this->shortPrefix = $shortPrefix;
    }

    public function createOptions()
    {
        $command = new \PHPUnit_TextUI_Command();
        $phpunitShowHelp = new \ReflectionMethod(get_class($command), 'showHelp');
        $phpunitShowHelp->setAccessible(true);

        ob_start();
        $phpunitShowHelp->invoke($command);
        $lines = explode("\n", ob_get_contents());
        ob_end_clean();

        $phpUnitTestRunner = new \ReflectionClass('PHPUnit_TextUI_TestRunner');
        $runnerProperty = $phpUnitTestRunner->getProperty("versionStringPrinted");
        $runnerProperty->setAccessible(true);

        // We need to reset this to false as the flag is set on the Runner class rather than on a specific instance,
        // causing problems in the context of a proxied command as the createOptions method needs to invoke a PHPUnit_TextUI_Command
        // which in turn sets that flag, thus preventing subsequent actual phpunit runs to access the version string.
        $runnerProperty->setValue(false);

        $options = [];

        foreach ($lines as $line) {
            $option = $this->createOptionFromHelpLine($line, $this->longPrefix, $this->shortPrefix);
            if (!empty($option)) {
                // skipping shortcut-only options
                if (empty($option['name'])) {
                    continue;
                }

                $options[$option['name']] = $option;
            }
        }

        return $options;
    }

    public function createOptionFromHelpLine($line)
    {
        $matches = [];
        $line = trim($line);

        if (empty($line)) {
            return null;
        }

        if (false === preg_match('/^(\-[a-z])?(\||\ )?([a-z=\[\]]+)?(\-\-[a-z-]+)?[ =]+(\<.+\>|\.+)?\ +(.*)?$/', $line, $matches)) {
            return null;
        }

        if (empty($matches)) {
            return null;
        }

        $mode = InputOption::VALUE_NONE;

        if (!empty($matches[3]) || !empty($matches[5])) {
            $mode = InputOption::VALUE_REQUIRED;
        }

        if (!empty($matches[3]) || '...' === $matches[5]) {
            $mode = $mode | InputOption::VALUE_IS_ARRAY;
        }

        $name = ltrim($matches[4], '-');
        $shortcut = rtrim(ltrim($matches[1], '-'), '|');

        return [
            'name' => !empty($name) ? $this->longPrefix . '-' . $name : '',
            'shortcut' => !empty($shortcut) ? $this->shortPrefix . $shortcut : '',
            'description' => trim($matches[6]),
            'mode' => $mode,
        ];
    }

    /**
     * Strips away long prefix from arguments and cleans the argv array from non-phpunit options.
     *
     * @todo check how to get current phpunit's script path
     */
    public function normalizeArgv($argv)
    {
        $normalizedArgv = ['phpunit'];
        $longPrefix = '--' . $this->longPrefix . '-';
        $shortPrefix = '-' . $this->shortPrefix;

        foreach ($argv as $key => $value) {
            if (0 === strpos($value, $longPrefix)) {
                $normalizedArgv[] = '--' . substr($value, strlen($longPrefix));
                continue;
            }

            if (0 === strpos($value, $shortPrefix)) {
                $normalizedArgv[] = '-' . substr($value, strlen($shortPrefix));
                continue;
            }

            $normalizedArgv[] = $value;
        }

        return $normalizedArgv;
    }
}
