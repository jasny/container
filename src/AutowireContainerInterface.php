<?php declare(strict_types=1);

namespace Jasny\Container;

use Psr\Container\ContainerInterface;

/**
 * Container that support autowiring.
 *
 * Autowiring resolved dependencies automatically by looking at the type and/or other
 * meta like phpdoc parameter info. These dependencies are loaded from the container
 * and passed to the constructor.
 */
interface AutowireContainerInterface extends ContainerInterface
{
    /**
     * Instantiate a new object, autowire dependencies.
     *
     * @param string $class    Class name
     * @param mixed  ...$args  Non-autowired arguments
     * @return object
     */
    public function autowire(string $class, ...$args);
}
