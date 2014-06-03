<?php

namespace Drupal\Core\Tests\Command;

use Drupal\Core\Test\RequestFactory;

class RequestFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultCreation()
    {
        $request = RequestFactory::createFromUri('http://localhost:80');

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Request', $request);

        $this->assertSame('http', $request->getScheme());
        $this->assertSame('localhost', $request->getHost());
        $this->assertSame(80, $request->getPort());
    }

    /**
     * @todo verify that not parsing query parameters is a wanted thing.
     */
    public function testCustomCreation()
    {
        $request = RequestFactory::createFromUri('https://www.foo.bar:8080/users/1?baz=quux');

        $this->assertInstanceOf('Symfony\\Component\\HttpFoundation\\Request', $request);

        $this->assertSame('https', $request->getScheme());
        $this->assertSame('www.foo.bar', $request->getHost());
        $this->assertSame(8080, $request->getPort());
        $this->assertSame('/users/1', $request->getBasePath());
        $this->assertSame([], $request->query->all());          // not sure that not parsing query params is a good idea.
    }
}
