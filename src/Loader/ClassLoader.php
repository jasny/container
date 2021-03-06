<?php

declare(strict_types=1);

namespace Jasny\Container\Loader;

use Improved as i;
use Jasny\Autowire\AutowireInterface;
use Psr\Container\ContainerInterface;

/**
 * Load entries from class.
 */
class ClassLoader extends AbstractLoader
{
    /**
     * Logic to create entries
     * @var callable
     */
    protected $apply;


    /**
     * MappingGenerator constructor.
     *
     * @param \Iterator<string> $classes
     * @param callable          $apply    Logic to create entries for a class
     */
    public function __construct(\Iterator $classes, callable $apply = null)
    {
        $this->apply = $apply ?? [$this, 'createEntry'];

        parent::__construct($classes);
    }


    /**
     * Create new entries
     *
     * @return void
     */
    protected function prepareNext(): void
    {
        if (!$this->items->valid()) {
            return;
        }

        $class = $this->items->current();

        $entries = i\type_check(
            call_user_func($this->apply, $class),
            'array',
            new \UnexpectedValueException("Failed to load container entries for '$class': "
                . "Expected array, callback returned %s")
        );

        $this->entries = new \ArrayIterator($entries);

        $this->items->next();
    }

    /**
     * Create a container entry for a class using autowiring.
     *
     * @param string $class
     * @return \Closure[]
     *
     * @template T of object
     * @phpstan-param class-string<T> $class
     */
    protected function createEntry(string $class)
    {
        return [
            $class => function (ContainerInterface $container) use ($class) {
                return $container->get(AutowireInterface::class)->instantiate($class);
            }
        ];
    }
}
