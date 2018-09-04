<?php

declare(strict_types=1);

namespace Jasny\Container;

use Psr\Container\ContainerInterface;

/**
 * Container that support autowiring.
 */
interface AutowireContainerInterface extends ContainerInterface
{
    /**
     * Instantiate a new object, autowire dependencies.
     *
     * @param string $class
     * @return object
     */
    public function autowire(string $class);
}