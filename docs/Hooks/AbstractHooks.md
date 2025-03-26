# AbstractHooks

<!-- @doc-source: AbstractHooks -->
Abstract base class for hook implementations

## Methods

### __construct
<!-- @doc-source: AbstractHooks.__construct -->
Abstract base class for hook implementations
/
abstract class AbstractHooks implements HookInterface
{
use LoggerTrait;

/**
Service container

#### Parameters

- ``: container Container

### getDefaultIdentifier
<!-- @doc-source: AbstractHooks.getDefaultIdentifier -->
Get default identifier based on class name

#### Returns



### getIdentifier
<!-- @doc-source: AbstractHooks.getIdentifier -->
Get hook identifier

#### Returns



### setIdentifier
<!-- @doc-source: AbstractHooks.setIdentifier -->
Set hook identifier

#### Parameters

- ``: string $identifier New identifier
- ``: identifier string

#### Returns



### getPriority
<!-- @doc-source: AbstractHooks.getPriority -->
Get priority for hook execution

#### Returns



### setPriority
<!-- @doc-source: AbstractHooks.setPriority -->
Set priority for hook execution

#### Parameters

- ``: int $priority New priority
- ``: priority int

#### Returns



### addAction
<!-- @doc-source: AbstractHooks.addAction -->
Add an action hook with the current object as the callback

#### Parameters

- ``: string $hook Hook name
- ``: string $method Method name to call
- ``: int $priority Priority (default 10)
- ``: int $acceptedArgs Number of arguments the callback accepts
- ``: hook string
- ``: method string
- ``: priority int
- ``: acceptedArgs int

#### Returns



### addFilter
<!-- @doc-source: AbstractHooks.addFilter -->
Add a filter hook with the current object as the callback

#### Parameters

- ``: string $hook Hook name
- ``: string $method Method name to call
- ``: int $priority Priority (default 10)
- ``: int $acceptedArgs Number of arguments the callback accepts
- ``: hook string
- ``: method string
- ``: priority int
- ``: acceptedArgs int

#### Returns



### removeAction
<!-- @doc-source: AbstractHooks.removeAction -->
Remove an action hook

#### Parameters

- ``: string $hook Hook name
- ``: string |callable $callback Callback to remove
- ``: int $priority Priority
- ``: hook string
- ``: callback mixed
- ``: priority int

#### Returns



### removeFilter
<!-- @doc-source: AbstractHooks.removeFilter -->
Remove a filter hook

#### Parameters

- ``: string $hook Hook name
- ``: string |callable $callback Callback to remove
- ``: int $priority Priority
- ``: hook string
- ``: callback mixed
- ``: priority int

#### Returns



### removeAllActions
<!-- @doc-source: AbstractHooks.removeAllActions -->
Remove all actions for a hook

#### Parameters

- ``: string $hook Hook name
- ``: hook string

#### Returns



### removeAllFilters
<!-- @doc-source: AbstractHooks.removeAllFilters -->
Remove all filters for a hook

#### Parameters

- ``: string $hook Hook name
- ``: hook string

#### Returns



### canRegister
<!-- @doc-source: AbstractHooks.canRegister -->
Determine if hooks should be registered
Default implementation always registers hooks

#### Returns



