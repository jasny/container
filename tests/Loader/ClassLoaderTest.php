<?php

namespace Jasny\Container\Tests\Loader;

use Jasny\Container\Autowire\AutowireInterface;
use Jasny\Container\Loader\ClassLoader;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @covers \Jasny\Container\Loader\ClassLoader
 * @covers \Jasny\Container\Loader\AbstractLoader
 */
class ClassLoaderTest extends TestCase
{
    public function testIterate()
    {
        $entries = new ClassLoader(new \ArrayIterator(['Foo', 'Bar', 'App\Qux']));

        $foo = new \stdClass();
        $bar = new \stdClass();
        $qux = new \stdClass();

        $autowire = $this->createMock(AutowireInterface::class);
        $autowire->expects($this->exactly(3))->method('instantiate')
            ->withConsecutive(['Foo'], ['Bar'], ['App\Qux'])->willReturnOnConsecutiveCalls($foo, $bar, $qux);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->any())->method('get')->with(AutowireInterface::class)
            ->willReturn($autowire);

        $result = [];

        foreach ($entries as $class => $callback) {
            $this->assertIsString($class);
            $this->assertInstanceOf(\Closure::class, $callback);

            $result[$class] = $callback($container);
        }

        $this->assertSame(['Foo' => $foo, 'Bar' => $bar, 'App\Qux' => $qux], $result);
    }

    public function testIterateCallback()
    {
        $apply = function ($class) {
            return [
                "$class.one" => function () use ($class) { return substr($class, 0, 1) . "1"; },
                "$class.two" => function () use ($class) { return substr($class, 0, 1) . "2"; }
            ];
        };

        $entries = new ClassLoader(new \ArrayIterator(['Foo', 'Bar']), $apply);

        $result = [];

        foreach ($entries as $class => $callback) {
            $this->assertIsString($class);
            $this->assertInstanceOf(\Closure::class, $callback);

            $result[$class] = $callback();
        }

        $this->assertSame(['Foo.one' => "F1", "Foo.two" => "F2", 'Bar.one' => "B1", 'Bar.two' => "B2"], $result);
    }

    public function testIterateSkip()
    {
        $apply = function ($class) {
            if ($class === 'Nop') {
                return [];
            }

            return [
                "$class.one" => function () use ($class) { return substr($class, 0, 1) . "1"; },
                "$class.two" => function () use ($class) { return substr($class, 0, 1) . "2"; }
            ];
        };

        $entries = new ClassLoader(new \ArrayIterator(['Foo', 'Nop', 'Bar']), $apply);

        $result = [];

        foreach ($entries as $class => $callback) {
            $this->assertIsString($class);
            $this->assertInstanceOf(\Closure::class, $callback);

            $result[$class] = $callback();
        }

        $this->assertSame(['Foo.one' => "F1", "Foo.two" => "F2", 'Bar.one' => "B1", 'Bar.two' => "B2"], $result);
    }

    public function testIteratorToArray()
    {
        $apply = function ($class) {
            return [
                "$class.one" => function () use ($class) { return substr($class, 0, 1) . "1"; },
                "$class.two" => function () use ($class) { return substr($class, 0, 1) . "2"; }
            ];
        };

        $entries = new ClassLoader(new \ArrayIterator(['Foo', 'Bar']), $apply);

        $result = iterator_to_array($entries);

        $this->assertEquals(['Foo.one', 'Foo.two', 'Bar.one', 'Bar.two'], array_keys($result));
        $this->assertContainsOnlyInstancesOf(\Closure::class, $result);
    }

    public function testIteratorToArrayEmpty()
    {
        $iterator = new \ArrayIterator([]);
        $entries = new ClassLoader($iterator);

        $result = iterator_to_array($entries);

        $this->assertEquals([], $result);
    }

    public function testIteratorToArrayRewind()
    {
        $apply = function ($class) {
            return [
                "$class.one" => function () use ($class) { return substr($class, 0, 1) . "1"; },
                "$class.two" => function () use ($class) { return substr($class, 0, 1) . "2"; }
            ];
        };

        $entries = new ClassLoader(new \ArrayIterator(['Foo', 'Bar']), $apply);

        $first = iterator_to_array($entries);
        $this->assertEquals(['Foo.one', 'Foo.two', 'Bar.one', 'Bar.two'], array_keys($first));

        $second = iterator_to_array($entries);
        $this->assertEquals(['Foo.one', 'Foo.two', 'Bar.one', 'Bar.two'], array_keys($second));
    }

    public function testIterateUnexpectedValue()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Failed to load container entries for 'Foo': Expected array, "
            . "callback returned instance of Closure");

        $apply = function () {
            return function() {};
        };

        $iterator = new \ArrayIterator(['Foo']);
        $entries = new ClassLoader($iterator, $apply);

        foreach ($entries as $key => $callback) {
            $callback();
        }
    }

    public function testGetInnerIterator()
    {
        $iterator = new \ArrayIterator([]);
        $entries = new ClassLoader($iterator);

        $this->assertSame($iterator, $entries->getInnerIterator());
    }
}
