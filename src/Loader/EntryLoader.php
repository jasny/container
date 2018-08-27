<?php

declare(strict_types=1);

namespace Jasny\Container\Loader;

use function Jasny\expect_type;

/**
 * Load entries from declaration PHP files
 */
class EntryLoader extends AbstractLoader
{
    /**
     * Load new entries
     *
     * @return void
     */
    protected function prepareNext(): void
    {
        if (!$this->items->valid()) {
            return;
        }

        $file = $this->items->current();

        $entries = include $file;
        expect_type($entries, 'array', \UnexpectedValueException::class,
            "Failed to load container entries from '$file': Invalid or no return value");

        $this->entries = new \ArrayIterator($entries);

        $this->items->next();
    }
}
