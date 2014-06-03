<?php

namespace Drupal\Core\Test;

use Drupal\Core\Site\Settings;

use Symfony\Component\HttpFoundation\Request;

/**
 * Encapsulate logic for bootstrapping Drupal during tests.
 */
class Bootstrapper
{
    private $request;
    private $drupalRoot;

    /**
     * @param string $drupalRoot  path of the Drupal root folder.
     */
    public function __construct($drupalRoot, $uri)
    {
        $this->drupalRoot = $drupalRoot;

        $this->request = RequestFactory::createFromUri($uri);
    }

    /**
     * Bootstraps a minimal Drupal environment.
     *
     * @see install_begin_request()
     */
    public function bootstrap()
    {
        // Load legacy include files.
        foreach (glob($this->drupalRoot . '/core/includes/*.inc') as $include) {
            require_once $include;
        }

        drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

        // Remove Drupal's error/exception handlers; they are designed for HTML
        // and there is no storage nor a (watchdog) logger here.
        restore_error_handler();
        restore_exception_handler();

        // In addition, ensure that PHP errors are not hidden away in logs.
        ini_set('display_errors', true);

        // Ensure that required Settings exist.
        if (!Settings::getAll()) {
            new Settings(array(
                'hash_salt' => 'run-tests',
            ));
        }

        $kernel = new TestKernel(drupal_classloader());
        $kernel->boot();

        $container = $kernel->getContainer();
        $container->enterScope('request');
        $container->set('request', $this->request, 'request');
        $container->get('request_stack')->push($this->request);

        $moduleHandler = $container->get('module_handler');
        $moduleHandler->loadAll();

        return $kernel;
    }
}
