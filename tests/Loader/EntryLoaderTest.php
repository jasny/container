<?php

namespace Jasny\Container\Tests\Loader;

use Jasny\Container\Loader\EntryLoader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\Container\Loader\EntryLoader
 * @covers \Jasny\Container\Loader\AbstractLoader
 */
class EntryLoaderTest extends TestCase
{
    protected $root;

    public function setUp(): void
    {
        $this->root = vfsStream::setup();
        vfsStream::create([
            'r.php' => "<?php return ['red' => function() { return 22; }];",
            'g.php' => "<?php return ['green' => function() { return 125; }];",
            'b.php' => "<?php return ['blue' => function() { return 48; }];",
            'multi.php' => "<?php return ['one' => function() { return 'I'; }, 'two' => function() { return 'II'; }];",
            'empty.php' => "<?php return [];",
            'blank.php' => "<?php "
        ]);
    }

    public function testIterate()
    {
        $iterator = new \ArrayIterator(['vfs://root/r.php', 'vfs://root/g.php', 'vfs://root/b.php']);
        $entries = new EntryLoader($iterator);

        $result = [];

        foreach ($entries as $key => $callback) {
            $this->assertIsString($key);
            $this->assertInstanceOf(\Closure::class, $callback);

            $result[$key] = $callback();
        }

        $this->assertEquals(['red' => 22, 'green' => 125, 'blue' => 48], $result);
    }

    public function testIterateMulti()
    {
        $iterator = new \ArrayIterator(['vfs://root/r.php', 'vfs://root/multi.php', 'vfs://root/g.php']);
        $entries = new EntryLoader($iterator);

        $result = [];

        foreach ($entries as $key => $callback) {
            $this->assertIsString($key);
            $this->assertInstanceOf(\Closure::class, $callback);

            $result[$key] = $callback();
        }

        $this->assertEquals(['red' => 22, 'one' => 'I', 'two' => 'II', 'green' => 125], $result);
    }

    public function testIterateEmpty()
    {
        $iterator = new \ArrayIterator(['vfs://root/r.php', 'vfs://root/empty.php', 'vfs://root/g.php']);
        $entries = new EntryLoader($iterator);

        $result = [];

        foreach ($entries as $key => $callback) {
            $this->assertIsString($key);
            $this->assertInstanceOf(\Closure::class, $callback);

            $result[$key] = $callback();
        }

        $this->assertEquals(['red' => 22, 'green' => 125], $result);
    }

    public function testIteratorToArray()
    {
        $iterator = new \ArrayIterator(['vfs://root/r.php', 'vfs://root/g.php', 'vfs://root/b.php']);
        $entries = new EntryLoader($iterator);

        $result = iterator_to_array($entries);

        $this->assertEquals(['red', 'green', 'blue'], array_keys($result));
        $this->assertContainsOnlyInstancesOf(\Closure::class, $result);
    }

    public function testIteratorToArrayEmpty()
    {
        $iterator = new \ArrayIterator([]);
        $entries = new EntryLoader($iterator);

        $result = iterator_to_array($entries);

        $this->assertEquals([], $result);
    }

    public function testIteratorToArrayRewind()
    {
        $iterator = new \ArrayIterator(['vfs://root/r.php', 'vfs://root/g.php', 'vfs://root/b.php']);
        $entries = new EntryLoader($iterator);

        $first = iterator_to_array($entries);
        $this->assertEquals(['red', 'green', 'blue'], array_keys($first));

        $second = iterator_to_array($entries);
        $this->assertEquals(['red', 'green', 'blue'], array_keys($second));
    }

    public function testIterateUnexpectedValue()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Failed to load container entries from 'vfs://root/blank.php': "
            . "Invalid or no return value");

        $iterator = new \ArrayIterator(['vfs://root/r.php', 'vfs://root/blank.php', 'vfs://root/g.php']);
        $entries = new EntryLoader($iterator);

        foreach ($entries as $key => $callback) {
            $callback();
        }
    }

    public function testGetInnerIterator()
    {
        $iterator = new \ArrayIterator();
        $entries = new EntryLoader($iterator);

        $this->assertSame($iterator, $entries->getInnerIterator());
    }
}
