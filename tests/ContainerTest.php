<?php

namespace Jasny\Container\Tests;

use Jasny\Autowire\AutowireInterface;
use Jasny\Container\Container;
use Jasny\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Test class for Container
 *
 * @covers \Jasny\Container\Container
 */
class ContainerTest extends TestCase
{

    public function testGet()
    {
        $container = new Container([
            "instance" => function () { return "value"; }
        ]);

        $this->assertSame('value', $container->get('instance'));
    }

    /**
     * @expectedException \Jasny\Container\Exception\NotFoundException
     */
    public function testGetNotFound()
    {
        $container = new Container([]);

        $container->get('nonexistant');
    }

    public function testDelegateContainer()
    {
        $container = new Container([
            "instance" => function () { return "value"; }
        ]);

        $container2 = new Container([
            "instance2" => function ($container) { return $container->get('instance'); }
        ], $container);

        $this->assertSame('value', $container2->get('instance2'));
    }

    public function testOneInstanceOnly()
    {
        $container = new Container([
            "instance" => function () { return new \stdClass(); }
        ]);

        $instance1 = $container->get('instance');
        $instance2 = $container->get('instance');

        $this->assertSame($instance1, $instance2);
    }

    public function testHas()
    {
        $container = new Container([
            "instance" => function () { return "value"; }
        ]);

        $this->assertTrue($container->has('instance'));
        $this->assertFalse($container->has('non_existing'));
    }


    public function testGetSub()
    {
        $subContainer = new Container([
            "instance" => function() { return "value"; }
        ]);

        $container = new Container([
            "sub" => function () use ($subContainer) { return $subContainer; },
            "instance" => function () { return "nop"; }
        ]);

        $result = $container->get('sub:instance');
        $this->assertEquals("value", $result);
    }

    /**
     * @expectedException \Jasny\Container\Exception\NoSubContainerException
     */
    public function testGetSubInvalid()
    {
        $container = new Container([
            "sub" => function () { return "value"; }
        ]);

        $container->get('sub:instance');
    }

    public function testHasSub()
    {
        $subContainer = new Container([
            "instance" => function() { return "value"; }
        ]);

        $container = new Container([
            "sub" => function () use ($subContainer) { return $subContainer; },
            "instance" => function () { return "nop"; }
        ]);

        $this->assertTrue($container->has('sub:instance'));

        $this->assertFalse($container->has('sub:non_existing'));
        $this->assertFalse($container->has('instance:foo'));
        $this->assertFalse($container->has('foo:instance'));
    }


    /**
     * @expectedException \PHPUnit\Framework\Error\Notice
     * @expectedExceptionMessage Entry is a DateTime object, which does not implement Psr\Container\ContainerInterface
     */
    public function testGetInterfaceMismatch()
    {
        $container = new Container([
            ContainerInterface::class => function () { return new \DateTime(); }
        ]);

        $container->get(ContainerInterface::class);
    }


    public function testAutowire()
    {
        $foo = new \stdClass();

        $autowire = $this->createMock(AutowireInterface::class);
        $autowire->expects($this->once())->method('instantiate')
            ->with('Foo')->willReturn($foo);

        $container = new Container([
            AutowireInterface::class => function() use ($autowire) {
                return $autowire;
            }
        ]);

        $result = $container->autowire('Foo');

        $this->assertSame($foo, $result);
    }

    /**
     * @expectedException Jasny\Container\Exception\NotFoundException
     */
    public function testAutowireNotFound()
    {
        $container = new Container([]);
        $container->autowire('Foo');
    }
}
