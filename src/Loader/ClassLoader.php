<?php

declare(strict_types=1);

namespace Jasny\Container\Loader;

use Psr\Container\ContainerInterface;

use function Jasny\expect_type;

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
     * @param \Iterator $classes
     * @param callable  $apply    Logic to create entries for a class
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
        $entries = call_user_func($this->apply, $class);

        expect_type(
            $entries,
            'array',
            \UnexpectedValueException::class,
            "Failed to load container entries for '$class': Expected array, callback returned %s"
        );

        $this->entries = new \ArrayIterator($entries);

        $this->items->next();
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
