<?php

namespace Jasny\Container\Tests;

use Jasny\Container;
use PHPUnit\Framework\TestCase;

/**
 * Test class for Container
 *
 * @covers Container
 */
class ContainerTest extends TestCase
{

    public function testGet()
    {
        $container = new Container([
            "instance" => function () { return "value"; },
        ]);

        $this->assertSame('value', $container->get('instance'));
    }

    /**
     * @expectedException \Jasny\Container\NotFoundException
     */
    public function testGetException()
    {
        $container = new Container([]);

        $container->get('nonexistant');
    }

    public function testDelegateContainer()
    {
        $container = new Container([
            "instance" => function () { return "value"; },
        ]);

        $container2 = new Container([
            "instance2" => function ($container) { return $container->get('instance'); },
        ], $container);

        $this->assertSame('value', $container2->get('instance2'));
    }

    public function testOneInstanceOnly()
    {
        $container = new Container([
            "instance" => function () { return new \stdClass(); },
        ]);

        $instance1 = $container->get('instance');
        $instance2 = $container->get('instance');

        $this->assertSame($instance1, $instance2);
    }

    public function testHas()
    {
        $container = new Container([
            "instance" => function () { return "value"; },
        ]);

        $this->assertTrue($container->has('instance'));
        $this->assertFalse($container->has('instance2'));
    }
}
