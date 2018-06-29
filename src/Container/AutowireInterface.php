<?php

namespace Jasny\Container;

/**
 * Interface for autowire service
 */
interface AutowireInterface
{
    /**
     * Instantiate a new object
     *
     * @param string $class
     * @return object
     */
    public function instantiate($class);

    /**
     * Must be an alias of the `instantiate` method
     *
     * @param string $class
     * @return object
     */
    public function __invoke($class);
}
