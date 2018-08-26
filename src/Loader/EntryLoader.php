<?php

declare(strict_types=1);

namespace Jasny\Container\Loader;

/**
 * Load entries from declaration PHP files
 */
class EntryLoader implements \Iterator
{
    /**
     * @var \GlobIterator
     */
    protected $glob;

    /**
     * @var \ArrayIterator
     */
    protected $entries;


    /**
     * EntryLoader constructor.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $flags = \GlobIterator::CURRENT_AS_PATHNAME | \GlobIterator::SKIP_DOTS;
        $this->glob = new \GlobIterator($path . '/*.php', $flags);

        $this->load();
    }

    /**
     * Load new entries
     *
     * @return void
     */
    protected function load(): void
    {
        if (!$this->glob->valid()) {
            return;
        }

        $file = $this->glob->current();

        $entries = include $file;
        $this->entries = new \ArrayIterator($entries);

        $this->glob->next();
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

        if (!$this->entries->valid()) {
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
