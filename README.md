Jasny Container
===

[![Build Status](https://travis-ci.org/jasny/container.svg?branch=master)](https://travis-ci.org/jasny/container)
[![Code Coverage](https://scrutinizer-ci.com/g/jasny/container/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jasny/container/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/jasny/container/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jasny/container/?branch=master)
[![Packagist Stable Version](https://img.shields.io/packagist/v/jasny/container.svg)](https://packagist.org/packages/jasny/container)
[![Packagist License](https://img.shields.io/packagist/l/jasny/container.svg)](https://packagist.org/packages/jasny/container)

This package contains a simple dependency injection container compatible with
[container-interop](https://github.com/container-interop/container-interop) (supports ContainerInterface and
**delegate lookup** feature).  It is also, therefore, compatible with
[PSR-11](https://github.com/php-fig/fig-standards/blob/master/proposed/container.md), the FIG container standard.

The container supports (explicit) [**autowiring**](#autowiring) and [**subcontainers**](#subcontainers).
`Container` objects are immutable.

Containers are used to help with [dependency injection](https://en.wikipedia.org/wiki/Dependency_injection), creating
loosly coupled applications. DI helps in making making your application better testable and maintainable.

The following type of entries are typically added to the container;

- **Services** are objects of for which there is typically only one instance that is available throughout the
  application. Here DI replaces the use of global objects, singletons and service locators.
- [**Abstract factories**](https://sourcemaking.com/design_patterns/abstract_factory) are services specific for creating
  new instances. Use factories instead of using `new` in your classes. The factory allows mocking objects or using a
  different/customized implementation of the class.
- [**Prototype objects**](https://sourcemaking.com/design_patterns/prototype) is an alternative to using factories,
  where the prototype object is immutable. The object will be clones when used.
- **Configuration values** can be directly returned by the container, this is preferable to using a global configuration
  array, global constants or getting environment variables directly.

_Pro tip:_ It's sometimes harder to get a dependency to a (deeply nested) object. In this case you might be tempted to
resort to using the global scope via a Service Locator, Facade or Singleton. **Do not do this!** It will make your code
much harder to test, maintain and reuse. Instead resolve the nesting by creating an abstract factory for the nested
object and inject the service into the factory.

_This library is based on [Picontainer](https://github.com/thecodingmachine/picotainer)._

Installation
---

The Jasny Container package is available on [packagist](https://packagist.org/packages/jasny/meta). Install it using
composer:

    composer require jasny/container

The packages adheres to the [SemVer](http://semver.org/) specification, and there will be full backward compatibility
between minor versions.

Declaring entries in the container
---

Creating a container is a matter of creating a `Container` instance passing the list of entries, as an
**array of anonymous functions**.

```php
use Jasny\Container\Container;
use Psr\Container\ContainerInterface;

$container = new Container([
    Foo::class => function(ContainerInterface $container) {
        return new Foo();
    },
    BarInterface::class => function(ContainerInterface $container) {
        $foo = $container->get(Foo::class);
        return new Bar($foo);
    },
    "bar" => function(ContainerInterface $container) {
        return $container->get('bar'); // Alias for BarInterface  
    },
    "APPLICATION_ENV" => function(ContainerInterface $container) {
        return getenv('APPLICATION_ENV');
    }
]);
```

The list of entries is an associative array. The order of entries doesn't matter.

- The key is the name of the entry in the container.
- The value is an **anonymous function** (Closure) that will return the entry.

The entry can be anything (an object, a scalar value, a resource, etc...) The anonymous function must accept one
parameter: the container on which dependencies will be fetched.
 
Any `iterable` may be passed to the container, not just plain arrays. Once the container has been created it's
**immutable** entries can't be added, removed or replaced.

#### Delegated lookup

If a delegate-lookup container was passed as the second argument of the constructor, it will be passed to the anonymous
function instead.

```php
$otherContainer = new Container([
    ZooInterface::class => function(ContainerInterface $container) {
        $foo = $container->get(Foo::class); // $container is the $rootContainer
        return new Zoo($foo);
    }
}, $rootContainer);
```

#### Entry loader

The `EntryLoader` can be used to load entries from PHP files in a directory. This is useful for larger applications to
organize service declarations.

```php
use Jasny\Container\Container;
use Jasny\Container\Loader\EntryLoader;

$files = new \GlobIterator(
    'path/to/declarations/*.php',
     \GlobIterator::CURRENT_AS_PATHNAME | \GlobIterator::SKIP_DOTS
);

$loader = new EntryLoader($files);
$container = new Container($loader);
```

The `EntryLoader` takes an Iterator. This can be an simple `ArrayIterator`, but more typically a `GlobIterator` or
a `RecursiveDirectoryIterator`. See [SPL Iterators](http://php.net/manual/en/spl.iterators.php).

#### Class loader

The `ClassLoader` is an alternative to the entry loader, to create entries based on a list of classes. The loader takes
an `Iterator` with fully qualified classnames (FQCNs).

```php
use Jasny\Container\Container;
use Jasny\Container\ClassLoader;

$loader = new ClassLoader(new \ArrayIterator(['App\Foo', 'App\Bar', 'App\Qux']));
$container = new Container($loader);
```

By default the entry key is the class name and autowiring is used to instantiate the service.

The second (optional) argument is a callback that is applied to each class to create the container entries. This
function must return an array of Closures.

```php
use Jasny\Container\Container;
use Jasny\Container\ClassLoader;
use Psr\Container\ContainerInterface;

$callback = function(string $class): array {
    $baseClass = preg_replace('/^.+\\/', '', $class);
    $id = Jasny\snakecase($class);

    return [
        $id => function(ContainerInterface $container) use ($class) {
            $colors = $container->get('colors');
            return new $class($colors);
        }
    ];
};

$loader = new ClassLoader(new \ArrayIterator(['App\Foo', 'App\Bar', 'App\Qux']), $callback);
$container = new Container($loader);
```

Instead of just supplying a list of classes, you might want to scan a folder and add all the classes from that folder.
This can be done with `FQCNIterator` from [jasny/fqcn-reader](https://github.com/jasny/fqcn-reader).

```php
use Jasny\Container\Container;
use Jasny\Container\Loader\ClassLoader;
use Jasny\FQCN\FQCNIterator;

$directoryIterator = new \RecursiveDirectoryIterator('path/to/project/services');
$recursiveIterator = new \RecursiveIteratorIterator($directoryIterator);
$sourceIterator = new \RegexIterator($recursiveIterator, '/^.+\.php$/i', \RegexIterator::GET_MATCH);

$fqcnIterator = new FQCNIterator($sourceIterator);

$loader = new ClassLoader($fqcnIterator);
$container = new Container($loader);
```

This can also be combined with a callback.

Fetching entries from the container
---

Fetching entries from the container is done using the `get` method:

```php
$bar = $container->get(BarInterface::class);
```

Calls to the `get` method should only return an entry if the entry is part of the container. If the entry is not part of
the container, a `Jasny\Container\NotFoundException` is thrown.

### Type checking

If the entry identifier is an interface or class name, a `TypeError` is thrown if the entry doesn't implement the
interface or extend the class.

Any identifier that starts with a capital and doesn't contain a `.` is seen a potential interface or class name, this is
checked with `class_exists`.

```php
// No type checking is done
$container->get('foo'); 
$container->get('datetime');
$container->get('My.thing');

// No checking is done because class doesn't exist
$container->get('FooBar');

// Checking is done
$container->get('Psr\Http\Message\ServerRequestInterface');
```

It's recommended to keep non-class/interface identifiers lowercase.

### Subcontainers

Entries of the container may also be a container themselves. In this case, you can use the `entry.subentry` to get an
entry from the subcontainer. The subcontainer needs to implement `Psr\Container\ContainerInterface`, it doesn't need to
be a `Jasny\Container` object.

```php
use Jasny\Container;
use Psr\Container\ContainerInterface;

$container = new Container([
    'config' => function(ContainerInterface $container) {
        return new Container([
            'secret' => function() {
                return getenv('APPLICATION_SECRET');
            }
        ]);
    }
]);

$secret = $container->get('config.secret');
```

If the container contains a `config.secret` entry, the `config` container is not consulted. A multiple levels are used
like `config.db.settings.host`, the container tries finding the an entry in the following order;
`config.db.settings.host`, `config.db.settings`, `config.db`, `config`.

### Autowiring

The container can be used to instantiate an object (instead of using `new`), automatically determining the dependencies,
using the [`jasny\autowire`](https://github.com/jasny/autowire) library. This can be handy when you find yourself
constantly modifying specific entries.

To use autowiring, add a `Jasny\AutowireInterface` entry to the container.

```php
use Jasny\Container;
use Jasny\Autowire\AutowireInterface;
use Jasny\Autowire\ReflectionAutowire;
use Psr\Container\ContainerInterface;

$container = new Container([
    AutowireInterface::class => function(ContainerInterface $container) {
        return new ReflectionAutowire($container);
    },
    Foo::class => function(ContainerInterface $container) {
        return new Foo();
    },
    BarInterface::class => function(ContainerInterface $container) {
        return $container->autowire(Bar::class);
    }
]);
```

_Pro tip:_ Autowiring increases coupling, so use it sparsely. For example different classes are set to use the `cache`
service. To use different caching methods, requires modifying the source of one (or both) of the classes.

### Checking entries

To check if the container has an entry you can use the `has` method. It return `true` if the entry is part of the
container and `false` otherwise.

#### Null object

_Pro tip:_ Rather than using the `has` method, create a
[Null object](https://sourcemaking.com/design_patterns/null_object). A null object correctly implements an interface,
but does nothing. For example a `NoCache` object that doesn't actually cache values. Removing the `if` statements
reduces complexity. The function calls are typically not more expensive than the if statement, so it doesn't hurt
performance.
