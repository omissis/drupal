<?php

namespace Drupal\Core\Test;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Test\Bootstrapper;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * @todo this class should not live under the scripts folder, but I put it here
 *       for now to keep modifications as local as possible.
 */
abstract class DrupalWebTestCase extends UnitTestCase
{
    protected static $bootstrapper;

    /**
     * @var Drupal\Core\DependencyInjection\ContainerBuilder
     */
    protected $container;

    /**
     * @var Drupal\Core\Test\TestKernel
     */
    protected $kernel;

    /**
     * @var array<string> modules to enable for each test run.
     */
    protected $modules;

    public function __construct($name = NULL, array $data = array(), $dataName = '') {
        if (empty(self::$bootstrapper)) {
            throw new \RuntimeException('Bootstrapper must be set before starting web tests.');
        }

        $this->kernel = self::$bootstrapper->bootstrap();
        $this->container = $this->kernel->getContainer();
    }

    public static function setBootstrapper(Bootstrapper $bootstrapper)
    {
        self::$bootstrapper = $bootstrapper;
    }

    public function setUp()
    {
        parent::setUp();

        $this->enableModules($this->getModulesToEnable());
    }

    /**
     * Force subclasses to declare what modules they need to enable.
     */
    public abstract function getModulesToEnable();

    /**
     * Enables modules for this test.
     *
     * @param array $modules
     *   A list of modules to enable. Dependencies are not resolved; i.e.,
     *   multiple modules have to be specified with dependent modules first.
     *   The new modules are only added to the active module list and loaded.
     */
    protected function enableModules(array $modules) {
        // Set the list of modules in the extension handler.
        $module_handler = $this->container->get('module_handler');

        // Write directly to active storage to avoid early instantiation of
        // the event dispatcher which can prevent modules from registering events.
        $active_storage =  $this->container->get('config.storage');
        $extensions = $active_storage->read('core.extension');

        foreach ($modules as $module) {
            $module_handler->addModule($module, drupal_get_path('module', $module));
            // Maintain the list of enabled modules in configuration.
            $extensions['module'][$module] = 0;
        }
        $active_storage->write('core.extension', $extensions);

        // Update the kernel to make their services available.
        $module_filenames = $module_handler->getModuleList();
        $this->kernel->updateModules($module_filenames, $module_filenames);

        // Ensure isLoaded() is TRUE in order to make _theme() work.
        // Note that the kernel has rebuilt the container; this $module_handler is
        // no longer the $module_handler instance from above.
        $module_handler = $this->container->get('module_handler');
        $module_handler->reload();
    }
}
