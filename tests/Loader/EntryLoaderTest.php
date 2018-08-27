<?php

namespace Jasny\Container\Tests\Loader;

use Jasny\Container\Loader\EntryLoader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Jasny\Container\Loader\EntryLoader
 */
class EntryLoaderTest extends TestCase
{
    protected $root;

    public function setUp()
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
            $this->assertInternalType('string', $key);
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
            $this->assertInternalType('string', $key);
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
            $this->assertInternalType('string', $key);
            $this->assertInstanceOf(\Closure::class, $callback);

            $result[$key] = $callback();
        }

        $this->assertEquals(['red' => 22, 'green' => 125], $result);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage
     */
    public function testIterateUnexpectedValue()
    {
        $iterator = new \ArrayIterator(['vfs://root/r.php', 'vfs://root/blank.php', 'vfs://root/g.php']);
        $entries = new EntryLoader($iterator);

        foreach ($entries as $key => $callback) {
            $callback();
        }
    }
}
