<?php

namespace Drupal\Core\PHPUnit;

use Symfony\Component\Console\Input\InputOption;

/**
 * Extract information from PHPUnit's Command class.
 *
 * @todo refactor this class to separate the factory methods from the transformers ones.
 *       it could also make sense to make its method non-static and inject config into its
 *       constructor instead of passing too many params to all the methods.
 */
class CommandUtils
{
    public static function createOptionsFromCommand(\PHPUnit_TextUI_Command $command, $longPrefix = 'phpunit', $shortPrefix = 'pu')
    {
        $phpunitShowHelp = new \ReflectionMethod(get_class($command), 'showHelp');
        $phpunitShowHelp->setAccessible(true);

        ob_start();
        $phpunitShowHelp->invoke($command);
        $lines = explode("\n", ob_get_contents());
        ob_end_clean();

        $options = [];

        foreach ($lines as $line) {
            $option = self::createOptionFromHelpLine($line, $longPrefix, $shortPrefix);
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

    public static function createOptionFromHelpLine($line, $longPrefix = 'phpunit', $shortPrefix = 'pu')
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
            'name' => !empty($name) ? $longPrefix . '-' . $name : '',
            'shortcut' => !empty($shortcut) ? $shortPrefix . $shortcut : '',
            'description' => trim($matches[6]),
            'mode' => $mode,
        ];
    }

    /**
     * Strips away long prefix from arguments and cleans the argv array from non-phpunit options.
     */
    public static function normalizeArgv($argv, $longPrefix = 'phpunit', $shortPrefix = 'pu')
    {
        $normalizedArgv = [];
        $longPrefix = '--' . $longPrefix . '-';
        $shortPrefix = '-' . $shortPrefix;

        foreach ($argv as $key => $value) {
            if (0 === strpos($value, $longPrefix)) {
                $normalizedArgv[] = '--' . substr($value, strlen($longPrefix));
            }

            if (0 === strpos($value, $shortPrefix)) {
                $normalizedArgv[] = '-' . substr($value, strlen($shortPrefix));
            }
        }

        return $normalizedArgv;
    }
}
