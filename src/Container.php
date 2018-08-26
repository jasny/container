<?php

declare(strict_types=1);

namespace Jasny\Container;

use Interop\Container\ContainerInterface as InteropContainer;
use Psr\Container\ContainerInterface as Psr11Container;
use Jasny\Container\Exception\NotFoundException;
use Jasny\Container\Exception\NoSubContainerException;

use function Jasny\expect_type;

/**
 * This class is a minimalist dependency injection container.
 * It has compatibility with container-interop's ContainerInterface and delegate-lookup feature.
 */
class Container implements InteropContainer
{
    /**
     * The delegate lookup.
     *
     * @var Psr11Container
     */
    protected $delegateLookupContainer;

    /**
     * The array of closures defining each entry of the container.
     *
     * @var \Closure[]
     */
    protected $callbacks;

    /**
     * The array of entries once they have been instantiated.
     *
     * @var mixed[]
     */
    protected $instances = [];


    /**
     * Class constructor
     *
     * @param iterable|\Closure[] $entries                 Entries must be passed as an array of anonymous functions.
     * @param Psr11Container      $delegateLookupContainer Optional delegate lookup container.
     */
    public function __construct(iterable $entries, Psr11Container $delegateLookupContainer = null)
    {
        $this->callbacks = is_array($entries) ? $entries : iterator_to_array($entries);
        $this->delegateLookupContainer = $delegateLookupContainer ?: $this;
    }


    /**
     * Get an instance from the container.
     *
     * @param string $identifier
     * @return mixed
     */
    public function get($identifier)
    {
        expect_type($identifier, 'string');

        if (strstr($identifier, ':') !== false) {
            return $this->getSub(...explode(':', $identifier, 2));
        }

        if (array_key_exists($identifier, $this->instances)) {
            return $this->instances[$identifier];
        }

        if (!isset($this->callbacks[$identifier])) {
            throw new NotFoundException("Entry \"$identifier\" is not defined.");
        }

        $instance = $this->callbacks[$identifier]($this->delegateLookupContainer);

        if (interface_exists($identifier) && !is_a($instance, $identifier)) {
            $type = (is_object($instance) ? get_class($instance) . ' ' : '') . gettype($instance);
            trigger_error("Entry is a $type, which does not implement $identifier", E_USER_NOTICE);
        }

        $this->instances[$identifier] = $instance;

        return $instance;
    }

    /**
     * Get an instance from a subcontainer
     *
     * @param string $identifier
     * @param string $subidentifier
     * @return mixed
     */
    protected function getSub(string $identifier, string $subidentifier)
    {
        $subcontainer = $this->get($identifier);

        if (!$subcontainer instanceof Psr11Container) {
            throw new NoSubContainerException("Entry \"$identifier\" is not a PSR-11 compatible container");
        }

        return $subcontainer->get($subidentifier);
    }


    /**
     * Check if the container has an entry.
     *
     * @param string $identifier
     * @return bool
     */
    public function has($identifier)
    {
        expect_type($identifier, 'string');

        if (strstr($identifier, ':') !== false) {
            return $this->hasSub(...explode(':', $identifier, 2));
        }

        return isset($this->callbacks[$identifier]);
    }

    /**
     * Get an instance from a subcontainer
     *
     * @param string $identifier
     * @param string $subidentifier
     * @return bool
     */
    protected function hasSub(string $identifier, string $subidentifier): bool
    {
        return $this->has($identifier) && $this->get($identifier) instanceof Psr11Container
            && $this->get($identifier)->has($subidentifier);
    }


    /**
     * Instantiate a new object, autowire dependencies.
     *
     * @param string $class
     * @return object
     */
    public function autowire(string $class)
    {
        return $this->get('Jasny\Autowire\AutowireInterface')->instantiate($class);
    }
}
