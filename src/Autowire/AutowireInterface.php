<?php declare(strict_types=1);

namespace Jasny\Container\Autowire;

/**
 * Interface for autowiring service
 */
interface AutowireInterface
{
    /**
     * Instantiate a new object
     *
     * @param string $class
     * @param mixed  ...$args
     * @return object
     */
    public function instantiate(string $class, ...$args);

    /**
     * Must be an alias of the `instantiate` method
     *
     * @param string $class
     * @param mixed  ...$args
     * @return object
     */
    public function __invoke($class, ...$args);
}
