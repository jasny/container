<?php

namespace Jasny\Container;

use Iterator;
use GlobIterator;
use ArrayIterator;

/**
 * Load entries from declaration PHP files
 */
class EntryLoader implements Iterator
{
    /**
     * @var GlobIterator
     */
    protected $glob;

    /**
     * @var ArrayIterator
     */
    protected $entries;


    /**
     * EntryLoader constructor.
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->glob = new GlobIterator($path . '/*.php', self::CURRENT_AS_PATHNAME | self::SKIP_DOTS);
        $this->load();
    }

    /**
     * Load new entries
     */
    protected function load()
    {
        if (!$this->glob->valid()) {
            return;
        }

        $file = $this->glob->current();

        $entries = include $file;
        $this->entries = new ArrayIterator($entries);

        $this->glob->next();
    }

    /**
     * Return the current element
     *
     * @return callable
     */
    public function current()
    {
        return $this->entries->current();
    }

    /**
     * Move forward to next element
     *
     * @return void Any returned value is ignored.
     */
    public function next()
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
     * @return boolean The return value will be casted to boolean and then evaluated.
     */
    public function valid()
    {
        return $this->entries->valid();
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        return $this->entries->rewind();
    }
}