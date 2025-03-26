# Container

<!-- @doc-source: Container -->


## Methods

### bind
<!-- @doc-source: Container.bind -->
Dependency Injection Container
Manages service bindings and resolutions
/

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\NotFoundException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Core\Traits\LoggerTrait;

class Container {
use LoggerTrait;

/**
Registered bindings

#### Parameters

- ``: abstract string
- ``: concrete mixed
- ``: shared bool

#### Returns



### singleton
<!-- @doc-source: Container.singleton -->
Register a singleton binding in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $concrete Concrete implementation or factory
- ``: abstract string
- ``: concrete mixed

#### Returns



### lazy
<!-- @doc-source: Container.lazy -->
Register a lazy service that will only be instantiated when needed

#### Parameters

- ``: string $abstract Abstract key
- ``: null \Closure $factory Factory function to create the service
- ``: bool $singleton Whether to treat as singleton
- ``: abstract string
- ``: factory \Closure
- ``: singleton bool

#### Returns



### instance
<!-- @doc-source: Container.instance -->
Register an existing instance in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $instance Concrete instance
- ``: abstract string
- ``: instance mixed

#### Returns



### get
<!-- @doc-source: Container.get -->
Resolve a binding from the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### resolveLazyService
<!-- @doc-source: Container.resolveLazyService -->
Resolve a lazy-loaded service

#### Parameters

- ``: string $abstract Service identifier
- ``: abstract string

#### Returns



### resolve
<!-- @doc-source: Container.resolve -->
Alias for get()

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### has
<!-- @doc-source: Container.has -->
Check if a binding exists in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### make
<!-- @doc-source: Container.make -->
Make an instance of the given class with automatic dependency injection

#### Parameters

- ``: string $concrete Class name to instantiate
- ``: array <string, mixed> $parameters Additional parameters to pass to constructor
- ``: concrete string
- ``: parameters array

#### Returns



### call
<!-- @doc-source: Container.call -->
Call a method with automatic dependency injection

#### Parameters

- ``: object |string $instance Object instance or class name for static methods
- ``: string $method Method name to call
- ``: array <string, mixed> $parameters Additional parameters to pass to method
- ``: instance mixed
- ``: method string
- ``: parameters array

#### Returns



### getBindings
<!-- @doc-source: Container.getBindings -->
Get all registered bindings

#### Returns



### getInstances
<!-- @doc-source: Container.getInstances -->
Get all resolved instances

#### Returns



### unbind
<!-- @doc-source: Container.unbind -->
Remove a binding from the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### clear
<!-- @doc-source: Container.clear -->
Clear all bindings and instances

#### Returns



