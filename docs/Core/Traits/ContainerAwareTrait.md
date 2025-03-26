# ContainerAwareTrait

<!-- @doc-source: ContainerAwareTrait -->


## Methods

### setContainer
<!-- @doc-source: ContainerAwareTrait.setContainer -->
Provides dependency injection container access to classes
/
trait ContainerAwareTrait
{
/**
Service container

#### Parameters

- ``: container Container

#### Returns



### getContainer
<!-- @doc-source: ContainerAwareTrait.getContainer -->
Get the container

#### Returns



### getService
<!-- @doc-source: ContainerAwareTrait.getService -->
Get service from container

#### Parameters

- ``: string $id Service identifier
- ``: bool $required Whether the service is required
- ``: id string
- ``: required bool

#### Returns



### getApplication
<!-- @doc-source: ContainerAwareTrait.getApplication -->
Get application instance

#### Returns



### hasService
<!-- @doc-source: ContainerAwareTrait.hasService -->
Check if a service exists in the container

#### Parameters

- ``: string $id Service identifier
- ``: id string

#### Returns



### makeInstance
<!-- @doc-source: ContainerAwareTrait.makeInstance -->
Create a new instance with automatic dependency injection

#### Parameters

- ``: string $class Class name
- ``: array $parameters Additional parameters
- ``: class string
- ``: parameters array

#### Returns



### call
<!-- @doc-source: ContainerAwareTrait.call -->
Call a method with automatic dependency injection

#### Parameters

- ``: object |string $instance Object instance or class name for static methods
- ``: string $method Method name
- ``: array $parameters Additional parameters
- ``: instance mixed
- ``: method string
- ``: parameters array

#### Returns



