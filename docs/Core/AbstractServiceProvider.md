# AbstractServiceProvider

<!-- @doc-source: AbstractServiceProvider -->


## Methods

### __construct
<!-- @doc-source: AbstractServiceProvider.__construct -->
Abstract base class for service providers
Provides common functionality and implements ServiceProvider interface
/

namespace FastyChild\Core;

use FastyChild\Core\Traits\LoggerTrait;
use FastyChild\Core\Traits\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProvider
{
use LoggerTrait;
use ContainerAwareTrait;

/**
Services provided by this provider

#### Parameters

- ``: container Container

### boot
<!-- @doc-source: AbstractServiceProvider.boot -->
Register services in the container

#### Returns



### provides
<!-- @doc-source: AbstractServiceProvider.provides -->
Get the services provided by the provider
Used for deferred loading

#### Returns



### singleton
<!-- @doc-source: AbstractServiceProvider.singleton -->
Register a singleton binding in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $concrete Concrete implementation or factory
- ``: abstract string
- ``: concrete mixed

#### Returns



### bind
<!-- @doc-source: AbstractServiceProvider.bind -->
Register a binding in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $concrete Concrete implementation or factory
- ``: abstract string
- ``: concrete mixed

#### Returns



### lazy
<!-- @doc-source: AbstractServiceProvider.lazy -->
Register a lazy service in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: null \Closure $factory Factory function to create the service
- ``: bool $singleton Whether to treat as singleton
- ``: abstract string
- ``: factory \Closure
- ``: singleton bool

#### Returns



### get
<!-- @doc-source: AbstractServiceProvider.get -->
Get a service from the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### has
<!-- @doc-source: AbstractServiceProvider.has -->
Check if a service exists in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### make
<!-- @doc-source: AbstractServiceProvider.make -->
Make an instance of a class with automatic dependency injection

#### Parameters

- ``: string $concrete Class name
- ``: array $parameters Additional constructor parameters
- ``: concrete string
- ``: parameters array

#### Returns



### call
<!-- @doc-source: AbstractServiceProvider.call -->
Call a method with automatic dependency injection

#### Parameters

- ``: object |string $instance Object instance or class name for static methods
- ``: string $method Method name
- ``: array $parameters Additional method parameters
- ``: instance mixed
- ``: method string
- ``: parameters array

#### Returns



