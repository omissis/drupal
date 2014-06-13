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
     * @var Drupal\Core\Test\TestKernel
     */
    protected $kernel;

    /**
     * @var array<string> modules to enable for each test run.
     */
    protected $modules;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        if (empty(self::$bootstrapper)) {
            throw new \RuntimeException('Bootstrapper must be set before starting web tests.');
        }

        $this->kernel = self::$bootstrapper->bootstrap();
    }

    public static function setBootstrapper(Bootstrapper $bootstrapper)
    {
        self::$bootstrapper = $bootstrapper;
    }

    public function setUp()
    {
        parent::setUp();

        $this->container()->get('config.storage')->write('core.extension', array('module' => array(), 'theme' => array()));

        $this->enableModules($this->getModulesToEnable());
    }

    /**
     * Force subclasses to declare what modules they need to enable.
     */
    public abstract function getModulesToEnable();

    /**
     * Debugging method.
     *
     * Used when writing new test cases and it's needed to list all the available services.
     */
    public function printContainerServiceIds()
    {
        foreach ($this->container()->getServiceIds() as $serviceId) {
            echo $serviceId . "\n";
        }
        exit;
    }

    /**
     * Helper method for getting kernel's container.
     *
     * @return \Drupal\Core\DependencyInjection\ContainerBuilder
     */
    protected function container()
    {
        return $this->kernel->getContainer();
    }

    /**
     * Installs default configuration for a given list of modules.
     *
     * @param array $modules
     *   A list of modules for which to install default configuration.
     */
    protected function installConfig(array $modules) {
        foreach ($modules as $module) {
            if (!$this->container()->get('module_handler')->moduleExists($module)) {
                throw new \RuntimeException(format_string("'@module' module is not enabled.", array(
                    '@module' => $module,
                )));
            }
            $this->container()->get('config.installer')->installDefaultConfig('module', $module);
        }
    }

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
        $module_handler = $this->container()->get('module_handler');

        // Write directly to active storage to avoid early instantiation of
        // the event dispatcher which can prevent modules from registering events.
        $active_storage =  $this->container()->get('config.storage');
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
        $module_handler = $this->container()->get('module_handler');
        $module_handler->reload();
    }
}
