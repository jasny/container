<?php

declare(strict_types=1);

namespace Jasny\Container\Loader;

/**
 * Base class for loader using two iterators.
 */
abstract class AbstractLoader implements \OuterIterator
{
    /**
     * @var \Iterator
     */
    protected $items;

    /**
     * @var \ArrayIterator
     */
    protected $entries;


    /**
     * Class constructor.
     *
     * @param \Iterator $items
     */
    public function __construct(\Iterator $items)
    {
        $this->items = $items;
        $this->prepareNext();
    }

    /**
     * Prepare new entries
     *
     * @return void
     */
    abstract protected function prepareNext(): void;

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

        while (!$this->entries->valid() && $this->items->valid()) {
            $this->prepareNext();
        }
    }

    /**
     * Return the key of the current element
     *
     * @return string|null
     */
    public function key(): ?string
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
        return isset($this->entries) && $this->entries->valid();
    }

    /**
     * Rewind the iterator to the first element
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->items->rewind();
        $this->entries = null;

        $this->prepareNext();
    }

    /**
     * Returns the inner iterator for the current entry.
     *
     * @return \Iterator
     */
    public function getInnerIterator(): \Iterator
    {
        return $this->items;
    }
}