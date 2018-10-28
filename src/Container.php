<?php declare(strict_types=1);

namespace Jasny\Container;

use Interop\Container\ContainerInterface as InteropContainer;
use Psr\Container\ContainerInterface as Psr11Container;
use Jasny\Container\Exception\NotFoundException;
use Jasny\Container\Exception\NoSubContainerException;

use function Jasny\expect_type;
use Psr\Container\ContainerInterface;

/**
 * This class is a minimalist dependency injection container.
 * It has compatibility with container-interop's ContainerInterface and delegate-lookup feature.
 */
class Container implements InteropContainer, AutowireContainerInterface
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
     * @param iterable<\Closure> $entries                 Entries must be passed as an array of anonymous functions.
     * @param Psr11Container     $delegateLookupContainer Optional delegate lookup container.
     */
    public function __construct(iterable $entries, Psr11Container $delegateLookupContainer = null)
    {
        $this->callbacks = $entries instanceof \Traversable ? iterator_to_array($entries) : $entries;
        $this->delegateLookupContainer = $delegateLookupContainer ?: $this;
    }


    /**
     * Get an instance from the container.
     *
     * @param string $identifier
     * @return mixed
     * @throws NotFoundException  if entry isn't defined
     */
    public function get($identifier)
    {
        expect_type($identifier, 'string');

        if (array_key_exists($identifier, $this->instances)) {
            return $this->instances[$identifier];
        }

        if (!isset($this->callbacks[$identifier])) {
            return $this->getSub($identifier);
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
     * Get an instance from a subcontainer.
     *
     * @param string $identifier
     * @return mixed
     * @throws NotFoundException
     */
    protected function getSub(string $identifier)
    {
        [$subcontainer, $containerId, $subId] = $this->findSubcontainer($identifier);

        if (!isset($subcontainer)) {
            throw new NotFoundException("Entry \"$identifier\" is not defined.");
        }

        if (!$subcontainer instanceof ContainerInterface) {
            throw new NoSubContainerException("Entry \"$containerId\" is not a PSR-11 compatible container");
        }


        return $subcontainer->get($subId);
    }


    /**
     * Check if the container has an entry.
     *
     * @param string $identifier
     * @return bool
     */
    public function has($identifier): bool
    {
        expect_type($identifier, 'string');

        return isset($this->callbacks[$identifier]) || $this->hasSub($identifier);
    }

    /**
     * Get an instance from a subcontainer.
     *
     * @param string $identifier
     * @return bool
     */
    protected function hasSub(string $identifier): bool
    {
        [$subcontainer, , $subId] = $this->findSubcontainer($identifier);

        return isset($subcontainer) && $subcontainer instanceof ContainerInterface && $subcontainer->has($subId);
    }

    /**
     * Find an subcontainer iterating through the identifier parts.
     *
     * @param string $identifier
     * @return array  [subcontainer, subidentifier]
     */
    protected function findSubcontainer(string $identifier): array
    {
        $containerId = null;
        $subcontainer = null;
        $parts = explode('.', $identifier);
        $subParts = [];

        while ($parts !== []) {
            array_unshift($subParts, array_pop($parts));
            $containerId = join('.', $parts);

            if (isset($this->callbacks[$containerId])) {
                $subcontainer = $this->get($containerId);
                break;
            }
        }

        return isset($subcontainer) ? [$subcontainer, $containerId, join('.', $subParts)] : [null, null, null];
    }


    /**
     * Instantiate a new object, autowire dependencies.
     *
     * @param string $class
     * @param mixed  ...$args
     * @return object
     */
    public function autowire(string $class, ...$args)
    {
        return $this->get('Jasny\Autowire\AutowireInterface')->instantiate($class, ...$args);
    }
}
