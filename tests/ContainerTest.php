<?php

namespace Jasny\Container\Tests;

use Jasny\Autowire\AutowireInterface;
use Jasny\Container\Container;
use Jasny\Container\Exception\NoSubContainerException;
use Jasny\Container\Exception\NotFoundException;
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

        $this->assertEquals('value', $container->get('instance'));
    }

    public function testGetNotFound()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container([]);
        $container->get('nonexistant');
    }

    public function testDelegateContainer()
    {
        $container = new Container([
            "instance" => function () { return "value"; }
        ]);

        $container2 = new Container([
            "instance2" => function (ContainerInterface $container) { return $container->get('instance'); }
        ], $container);

        $this->assertEquals('value', $container2->get('instance2'));
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

        $result = $container->get('sub.instance');
        $this->assertEquals("value", $result);
    }

    public function testGetSubInvalid()
    {
        $this->expectException(NoSubContainerException::class);

        $container = new Container([
            "sub" => function () { return "value"; }
        ]);

        $container->get('sub.instance');
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

        $this->assertTrue($container->has('sub.instance'));

        $this->assertFalse($container->has('sub.non_existing'));
        $this->assertFalse($container->has('instance.foo'));
        $this->assertFalse($container->has('foo.instance'));
    }

    public function testHasDeepSub()
    {
        $subContainer = new Container([
            "d1.d2.instance" => function() { return "value"; }
        ]);

        $container = new Container([
            "sub.u1.u2" => function () use ($subContainer) { return $subContainer; },
        ]);

        $this->assertTrue($container->has('sub.u1.u2.d1.d2.instance'));

        $this->assertFalse($container->has('sub.u1.u2.d1'));
    }


    public function testGetClassMismatch()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Entry is a DateTime object, which does not implement Jasny\Container\Container");

        $container = new Container([
            Container::class => function () { return new \DateTime(); }
        ]);

        $container->get(Container::class);
    }

    public function testGetInterfaceMismatch()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Entry is a DateTime object, which does not implement "
            . "Psr\Container\ContainerInterface");

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

    public function testAutowireParams()
    {
        $foo = new \stdClass();

        $autowire = $this->createMock(AutowireInterface::class);
        $autowire->expects($this->once())->method('instantiate')
            ->with('Foo', 'one', 'two')->willReturn($foo);

        $container = new Container([
            AutowireInterface::class => function() use ($autowire) {
                return $autowire;
            }
        ]);

        $result = $container->autowire('Foo', 'one', 'two');

        $this->assertSame($foo, $result);
    }

    public function testAutowireNotFound()
    {
        $this->expectException(NotFoundException::class);

        $container = new Container([]);
        $container->autowire('Foo');
    }

    public function testWith()
    {
        $redContainer = new Container([
            "color" => function () { return "red"; },
            "colour" => function (Container $container) { return $container->get('color');},
            "instance" => function () { return "value"; },
        ]);

        // Make sure the instances are set
        $this->assertEquals('red', $redContainer->get('color'));
        $this->assertEquals('red', $redContainer->get('colour'));

        $blueController = $redContainer->with([
            'color' => function () { return "blue"; },
        ]);

        $this->assertInstanceOf(Container::class, $blueController);
        $this->assertNotSame($redContainer, $blueController);

        $this->assertEquals('red', $redContainer->get('color'));
        $this->assertEquals('blue', $blueController->get('color'));

        $this->assertEquals('red', $redContainer->get('colour'));
        $this->assertEquals('blue', $blueController->get('colour'));

        $this->assertEquals('value', $redContainer->get('instance'));
        $this->assertEquals('value', $blueController->get('instance'));
    }

    public function testWithUsingDelegateContainer()
    {
        $container = new Container([
            "instanceA" => function () { return "A"; },
            "instanceB" => function () { return "B"; },
        ]);

        $containerA = new Container([
            "instance" => function (ContainerInterface $container) { return $container->get('instanceA'); },
        ], $container);

        $containerB = $containerA->with([
            "instance" => function (ContainerInterface $container) { return $container->get('instanceB'); },
        ]);

        $this->assertEquals('A', $containerA->get('instance'));
        $this->assertEquals('B', $containerB->get('instance'));
    }
}
