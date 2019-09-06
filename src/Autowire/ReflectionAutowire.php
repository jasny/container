<?php declare(strict_types=1);

namespace Jasny\Container\Autowire;

use Jasny\Container\Exception\AutowireException;
use Jasny\ReflectionFactory\ReflectionFactory;
use Jasny\ReflectionFactory\ReflectionFactoryInterface;
use Psr\Container\ContainerInterface as Psr11Container;

/**
 * Autowiring using reflection and annotations.
 */
class ReflectionAutowire implements AutowireInterface
{
    /**
     * @var Psr11Container
     */
    protected $container;

    /**
     * @var ReflectionFactoryInterface
     */
    protected $reflection;

    /**
     * ReflectionAutowire constructor.
     *
     * @param Psr11Container              $container
     * @param ReflectionFactoryInterface  $reflection
     */
    public function __construct(Psr11Container $container, ReflectionFactoryInterface $reflection = null)
    {
        $this->container = $container;
        $this->reflection = $reflection ?? new ReflectionFactory();
    }


    /**
     * Get annotations for the constructor parameters.
     * Annotated parameter types are not considered. Turning the class to a FQCN is more work than it's worth.
     *
     * @param string $docComment
     * @return array
     */
    protected function extractParamAnnotations(string $docComment): array
    {
        $pattern = '/@param(?:\s+([^$"]\S+))?(?:\s+\$(\w+))?(?:\s+"([^"]++)")?/';

        if (!(bool)preg_match_all($pattern, $docComment, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $annotations = [];

        foreach ($matches as $index => $match) {
            $annotations[$index] = isset($match[3]) && $match[3] !== '' ? $match[3] : null;
        }

        return $annotations;
    }

    /**
     * Get the declared type of a parameter.
     *
     * @param \ReflectionClass     $class
     * @param \ReflectionParameter $param
     * @return string
     */
    protected function getParamType(\ReflectionClass $class, \ReflectionParameter $param): string
    {
        $reflType = $param->getType();

        if ($reflType === null || !$reflType instanceof \ReflectionNamedType) {
            throw new AutowireException("Unable to autowire " . $class->getName() . ": "
                . "Unknown type for parameter '" . $param->getName() . "'.");
        }

        if ($reflType->isBuiltin()) {
            throw new AutowireException("Unable to autowire " . $class->getName() . ": "
                . "Build-in type '" . $reflType->getName() . "' for parameter '" . $param->getName() . "' can't be "
                . "used as container id. Please specify via @param.");
        }

        return $reflType->getName();
    }

    /**
     * Get all dependencies for a class constructor.
     *
     * @param \ReflectionClass $class
     * @param int $skip Number of parameters to skip
     * @return array[]
     * @throws \ReflectionException
     */
    protected function determineDependencies(\ReflectionClass $class, int $skip): array
    {
        if (!$class->hasMethod('__construct')) {
            return [];
        }

        $constructor = $class->getMethod('__construct');
        $docComment = $constructor->getDocComment();
        $annotations = is_string($docComment) ? $this->extractParamAnnotations($docComment) : [];

        $identifiers = [];

        $params = $constructor->getParameters();
        $consideredParams = $skip === 0 ? $params : array_slice($params, $skip, null, true);

        foreach ($consideredParams as $index => $param) {
            $identifiers[$index] = [
                'key' => $annotations[$index] ?? $this->getParamType($class, $param),
                'optional' => $param->allowsNull(),
            ];
        }

        return $identifiers;
    }

    /**
     * Get dependencies from the container
     *
     * @param array[] $identifiers
     * @return array
     */
    protected function getDependencies(array $identifiers): array
    {
        $dependencies = [];

        foreach ($identifiers as $index => ['key' => $key, 'optional' => $optional]) {
            $dependencies[$index] = !(bool)$optional || $this->container->has($key)
                ? $this->container->get($key)
                : null;
        };

        return $dependencies;
    }

    /**
     * Instantiate a new object, automatically injecting dependencies
     *
     * @param string $class
     * @param mixed  ...$args  Additional arguments are passed to the constructor directly.
     * @return object
     * @throws AutowireException
     */
    public function instantiate(string $class, ...$args)
    {
        try {
            $refl = $this->reflection->reflectClass($class);

            $dependencyIds = $this->determineDependencies($refl, count($args));
            $dependencies = $args + $this->getDependencies($dependencyIds);
        } catch (\ReflectionException $exception) {
            throw new AutowireException("Unable to autowire {$class}: " . $exception->getMessage(), 0, $exception);
        }

        return $refl->newInstanceArgs($dependencies);
    }

    /**
     * Alias of `instantiate` method
     *
     * @param string $class
     * @param mixed  ...$args  Additional arguments are passed to the constructor directly.
     * @return object
     * @throws AutowireException
     * @throws \ReflectionException
     */
    final public function __invoke($class, ...$args)
    {
        return $this->instantiate($class, ...$args);
    }
}
