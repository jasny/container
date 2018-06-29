<?php

namespace Jasny\Container\Autowire;

use Jasny\Container\AutowireInterface;
use ReflectionClass;
use Psr\Container\ContainerInterface as Psr11Container;

/**
 * Autowire using reflection and annotations
 */
class ReflectionAutowire implements AutowireInterface
{
    /**
     * @var Psr11Container
     */
    protected $container;


    /**
     * ReflectionAutowire constructor.
     *
     * @param Psr11Container $container
     */
    public function __construct(Psr11Container $container)
    {
        $this->container = $container;
    }


    /**
     * Get annotations for the contructor parameters.
     *
     * @param string $docComment
     * @return array
     */
    protected function extractParamAnnotations($docComment)
    {
        $pattern = '/@param(?:\s+[^$"]\S+)?(?:\s+\$\w+)(?:\s+"([^"]++)")/';

        if (!preg_match_all($pattern, $docComment, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $annotations = [];

        foreach ($matches as $match) {
            $annotations[] = $match[2] ?? null;
        }

        return $annotations;
    }

    /**
     * Get all dependencies for a class constructor.
     *
     * @param ReflectionClass $class
     * @return array
     */
    protected function getDependencies(ReflectionClass $class)
    {
        if (!$class->hasMethod('__construct')) {
            return [];
        }

        $constructor = $class->getMethod($class, '__construct');
        $annotations = $this->extractParamAnnotations($constructor->getDocComment());

        $identifiers = [];

        foreach ($constructor->getParameters() as $i =>$param) {
            $identifiers[] = $annotations[$i] ?: $param->getType()->name;
        }

        return $identifiers;
    }

    /**
     * Instantiate a new object, automatically injecting dependencies
     *
     * @param string $class
     * @return object
     * @throws \ReflectionException
     */
    public function instantiate($class)
    {
        $refl = new ReflectionClass($class);

        $dependencies = array_map(function ($identifier) {
            return $this->container->get($identifier);
        }, $this->getDependencies($class));

        return $refl->newInstanceArgs($dependencies);
    }

    /**
     * Alias of `instantiate` method
     *
     * @param string $class
     * @return object
     * @throws \ReflectionException
     */
    final public function __invoke($class)
    {
        return $this->instantiate($class);
    }
}
