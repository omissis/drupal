<?php

namespace Drupal\Core\Test;

use Drupal\Core\Site\Settings;
use Drupal\Core\Language\Language;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Config\FileStorage;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Request;

/**
 * Encapsulate logic for bootstrapping Drupal during tests.
 */
class Bootstrapper
{
    private $kernel;
    private $request;
    private $container;
    private $drupalRoot;

    /**
     * @param string $drupalRoot  path of the Drupal root folder.
     */
    public function __construct($drupalRoot, $uri)
    {
        $this->drupalRoot = $drupalRoot;

        $this->request = RequestFactory::createFromUri($uri);
        $this->container = new ContainerBuilder();
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

        $this->kernel = new TestKernel(drupal_classloader());
        $this->kernel->boot();

        $container = $this->kernel->getContainer();

        $container->set('request', $this->request);
        $container->get('request_stack')->push($this->request);

        $container->set('config.storage', new FileStorage(sys_get_temp_dir(), 'drupal_web_test'));

        drupal_bootstrap(DRUPAL_BOOTSTRAP_CODE);

        return $this->kernel;
    }
}
