<?php

declare(strict_types=1);

namespace Jasny\Container\Loader;

use function Jasny\expect_type;

/**
 * Load entries from declaration PHP files
 */
class EntryLoader implements \Iterator
{
    /**
     * @var \Iterator
     */
    protected $files;

    /**
     * @var \ArrayIterator
     */
    protected $entries;


    /**
     * EntryLoader constructor.
     *
     * @param \Iterator $files
     */
    public function __construct(\Iterator $files)
    {
        $this->files = $files;
        $this->load();
    }

    /**
     * Load new entries
     *
     * @return void
     */
    protected function load(): void
    {
        if (!$this->files->valid()) {
            return;
        }

        $file = $this->files->current();

        $entries = include $file;
        expect_type($entries, 'array', \UnexpectedValueException::class,
            "Failed to load container entries from '$file': Expected array, %s returned");

        $this->entries = new \ArrayIterator($entries);

        $this->files->next();
    }

    /**
     * Return the current element
     *
     * @return callable
     */
    public function current(): callable
    {
        return $this->entries->current();
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next(): void
    {
        $this->entries->next();

        while (!$this->entries->valid() && $this->files->valid()) {
            $this->load();
        }
    }

    /**
     * Return the key of the current element
     *
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->entries->key();
    }

    /**
     * Checks if current position is valid
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->entries->valid();
    }

    /**
     * Rewind the iterator to the first element
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->entries->rewind();
    }
}
