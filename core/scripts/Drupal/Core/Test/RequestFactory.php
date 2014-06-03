<?php

namespace Drupal\Core\Test;

use Symfony\Component\HttpFoundation\Request;

/**
 * Encapsulate logic for bootstrapping Drupal during tests.
 */
class RequestFactory
{
    public static function createFromUri($uri, $port = 80)
    {
        $parsedUrl = parse_url($uri);
        $host = $parsedUrl['host'] . (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '');
        $path = isset($parsedUrl['path']) ? rtrim(rtrim($parsedUrl['path']), '/') : '';
        $port = (isset($parsedUrl['port']) ? $parsedUrl['port'] : $port);

        if ($path == '/') {
            $path = '';
        }

        $_SERVER = array_merge($_SERVER, [
            'HTTP_HOST' => $host,
            'REMOTE_ADDR' => '127.0.0.1',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => $port,
            'SERVER_SOFTWARE' => NULL,
            'SERVER_NAME' => 'localhost',
            'REQUEST_URI' => $path .'/',
            'REQUEST_METHOD' => 'GET',
            'SCRIPT_NAME' => $path .'/index.php',
            'SCRIPT_FILENAME' => $path .'/index.php',
            'PHP_SELF' => $path .'/index.php',
            'HTTP_USER_AGENT' => 'Drupal command line',
        ]);

        // If the passed URL schema is 'https' then setup the $_SERVER variables
        // properly so that testing will run under HTTPS.
        if ($parsedUrl['scheme'] == 'https') {
            $_SERVER['HTTPS'] = 'on';
        }

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            // Ensure that any and all environment variables are changed to https://.
            foreach ($_SERVER as $key => $value) {
                $_SERVER[$key] = str_replace('http://', 'https://', $_SERVER[$key]);
            }
        }

        return Request::createFromGlobals();
    }
}
