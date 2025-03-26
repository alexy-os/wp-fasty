# AbstractHook

<!-- @doc-source: AbstractHook -->
Abstract base class for hook implementations

## Methods

### __construct
<!-- @doc-source: AbstractHook.__construct -->
Abstract base class for hook implementations
/
abstract class AbstractHook implements HookInterface
{
use LoggerTrait;

/**
Service container

#### Parameters

- ``: container Container

### getDefaultIdentifier
<!-- @doc-source: AbstractHook.getDefaultIdentifier -->
Get default identifier based on class name

#### Returns



### getIdentifier
<!-- @doc-source: AbstractHook.getIdentifier -->
Get hook identifier

#### Returns



### setIdentifier
<!-- @doc-source: AbstractHook.setIdentifier -->
Set hook identifier

#### Parameters

- ``: string $identifier New identifier
- ``: identifier string

#### Returns



### getPriority
<!-- @doc-source: AbstractHook.getPriority -->
Get priority for hook execution

#### Returns



### setPriority
<!-- @doc-source: AbstractHook.setPriority -->
Set priority for hook execution

#### Parameters

- ``: int $priority New priority
- ``: priority int

#### Returns



### addAction
<!-- @doc-source: AbstractHook.addAction -->
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
<!-- @doc-source: AbstractHook.addFilter -->
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
<!-- @doc-source: AbstractHook.removeAction -->
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
<!-- @doc-source: AbstractHook.removeFilter -->
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
<!-- @doc-source: AbstractHook.removeAllActions -->
Remove all actions for a hook

#### Parameters

- ``: string $hook Hook name
- ``: hook string

#### Returns



### removeAllFilters
<!-- @doc-source: AbstractHook.removeAllFilters -->
Remove all filters for a hook

#### Parameters

- ``: string $hook Hook name
- ``: hook string

#### Returns



### canRegister
<!-- @doc-source: AbstractHook.canRegister -->
Determine if hooks should be registered
Default implementation always registers hooks

#### Returns



