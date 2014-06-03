<?php

namespace Drupal\Core\Tests\Test;

use Symfony\Component\Console\Application;
use Drupal\Core\Test\Bootstrapper;

class BootstrapperTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->drupalRoot = dirname(dirname(dirname(dirname(dirname(dirname(__DIR__))))));
        $this->bootstrapper = new Bootstrapper($this->drupalRoot, 'http://foo.bar');
    }

    public function tearDown()
    {
        $this->bootstrapper = null;
    }

    public function testBootstrap()
    {
        $this->assertNotNull($this->bootstrapper);
        $this->assertInstanceOf('Drupal\\Core\\Test\\TestKernel', $this->bootstrapper->bootstrap());
    }
}
