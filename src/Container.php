<?php declare(strict_types=1);

namespace Jasny\Container;

use Improved as i;
use Interop\Container\ContainerInterface as InteropContainer;
use Jasny\Autowire\AutowireInterface;
use Psr\Container\ContainerInterface;
use Jasny\Container\Exception\NotFoundException;
use Jasny\Container\Exception\NoSubContainerException;

/**
 * This class is a minimalist dependency injection container.
 * It has compatibility with container-interop's ContainerInterface and delegate-lookup feature.
 */
class Container implements InteropContainer, AutowireContainerInterface
{
    /**
     * The delegate lookup.
     *
     * @var ContainerInterface
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
     * @param \Traversable<\Closure>|\Closure[] $entries  Entries must be passed as an array of anonymous functions.
     * @param ContainerInterface|null $delegateLookup     Optional delegate lookup container.
     */
    public function __construct(iterable $entries, ?ContainerInterface $delegateLookup = null)
    {
        $this->callbacks = is_array($entries) ? $entries : iterator_to_array($entries);
        $this->delegateLookupContainer = $delegateLookup ?: $this;
    }


    /**
     * Get an instance from the container.
     *
     * @param string $identifier
     * @return mixed
     * @throws NotFoundException
     * @throws NoSubContainerException
     */
    public function get($identifier)
    {
        i\type_check($identifier, 'string');

        if (array_key_exists($identifier, $this->instances)) {
            return $this->instances[$identifier];
        }

        if (!isset($this->callbacks[$identifier])) {
            return $this->getSub($identifier);
        }

        $instance = $this->callbacks[$identifier]($this->delegateLookupContainer);
        $this->assertType($instance, $identifier);

        $this->instances[$identifier] = $instance;

        return $instance;
    }

    /**
     * Check if the container has an entry.
     *
     * @param string $identifier
     * @return bool
     * @throws NotFoundException
     * @throws NoSubContainerException
     */
    public function has($identifier): bool
    {
        i\type_check($identifier, 'string');

        return isset($this->callbacks[$identifier]) || $this->hasSub($identifier);
    }

    /**
     * Instantiate a new object, autowire dependencies.
     *
     * @param string $class
     * @param mixed ...$args
     * @return object
     * @throws NotFoundException
     * @throws NoSubContainerException
     */
    public function autowire(string $class, ...$args)
    {
        return $this->get(AutowireInterface::class)->instantiate($class, ...$args);
    }


    /**
     * Check the type of the instance if the identifier is an interface or class name.
     *
     * @param mixed  $instance
     * @param string $identifier
     * @throws \TypeError
     */
    protected function assertType($instance, string $identifier): void
    {
        if (ctype_upper($identifier[0]) &&
            strpos($identifier, '.') === false &&
            (class_exists($identifier) || interface_exists($identifier)) &&
            !is_a($instance, $identifier)
        ) {
            $type = (is_object($instance) ? get_class($instance) . ' ' : '') . gettype($instance);
            throw new \TypeError("Entry is a $type, which does not implement $identifier");
        }
    }


    /**
     * Get an instance from a subcontainer.
     *
     * @param string $identifier
     * @return mixed
     * @throws NotFoundException
     * @throws NoSubContainerException
     */
    protected function getSub(string $identifier)
    {
        [$subcontainer, $containerId, $subId] = $this->findSubContainer($identifier);

        if (!isset($subcontainer)) {
            throw new NotFoundException("Entry \"$identifier\" is not defined.");
        }

        if (!$subcontainer instanceof ContainerInterface) {
            throw new NoSubContainerException("Entry \"$containerId\" is not a PSR-11 compatible container");
        }

        return $subcontainer->get($subId);
    }

    /**
     * Get an instance from a subcontainer.
     *
     * @param string $identifier
     * @return bool
     * @throws NotFoundException
     * @throws NoSubContainerException
     */
    protected function hasSub(string $identifier): bool
    {
        [$subcontainer, , $subId] = $this->findSubContainer($identifier);

        return isset($subcontainer) && $subcontainer instanceof ContainerInterface && $subcontainer->has($subId);
    }

    /**
     * Find an subcontainer iterating through the identifier parts.
     *
     * @param string $identifier
     * @return array  [subcontainer, container id, subidentifier]
     * @throws NotFoundException
     * @throws NoSubContainerException
     */
    protected function findSubContainer(string $identifier): array
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
}
