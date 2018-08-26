<?php

declare(strict_types=1);

namespace Jasny\Container\Loader;

use Psr\Container\ContainerInterface;

/**
 * Load entries from class.
 */
class ClassLoader implements \OuterIterator
{
    /**
     * Logic to create entries
     * @var callable
     */
    protected $apply;

    /**
     * @var \Iterator
     */
    protected $classes;


    /**
     * MappingGenerator constructor.
     *
     * @param \Iterator $classes
     * @param callable  $apply    Logic to create entries for a class
     */
    public function __construct(\Iterator $classes, callable $apply = null)
    {
        $this->classes = $classes;
        $this->apply = $apply ?? [$this, 'createEntry'];
    }


    /**
     * Return the current element
     *
     * @return \Closure[]|null
     */
    public function current(): ?array
    {
        return call_user_func($this->apply, $this->classes->current());
    }

    /**
     * Move forward to next element
     *
     * @return void
     */
    public function next(): void
    {
        $this->classes->next();
    }

    /**
     * Return the key of the current element
     *
     * @return string|null
     */
    public function key(): ?string
    {
        return $this->classes->current();
    }

    /**
     * Checks if current position is valid
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return $this->classes->valid();
    }

    /**
     * Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->classes->rewind();
    }

    /**
     * Returns the inner iterator for the current entry.
     *
     * @return \Iterator
     */
    public function getInnerIterator(): \Iterator
    {
        return $this->classes;
    }


    /**
     * Create a container entry for a class using autowiring.
     *
     * @param string $class
     * @return \Closure[]
     */
    protected function createEntry(string $class)
    {
        return [
            $class => function (ContainerInterface $container) use ($class) {
                return $container->get('Jasny\Autowire\AutowireInterface')->instantiate($class);
            }
        ];
    }
}
