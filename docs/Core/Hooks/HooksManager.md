# HooksManager

<!-- @doc-source: HooksManager -->
Manages WordPress hook implementations

## Methods

### __construct
<!-- @doc-source: HooksManager.__construct -->
Manages WordPress hook implementations
/
class HooksManager
{
use LoggerTrait;

/**
Service container

#### Parameters

- ``: container Container

### addHook
<!-- @doc-source: HooksManager.addHook -->
Add a hook implementation

#### Parameters

- ``: string $name Hook name
- ``: string |HookInterface $hookClass Hook class name or instance
- ``: name string
- ``: hookClass mixed

#### Returns



### registerHooks
<!-- @doc-source: HooksManager.registerHooks -->
Register all hooks with WordPress

#### Returns



### sortHooksByPriority
<!-- @doc-source: HooksManager.sortHooksByPriority -->
Sort hooks by priority

#### Returns



### getHook
<!-- @doc-source: HooksManager.getHook -->
Get a specific hook by name

#### Parameters

- ``: string $name Hook name
- ``: name string

#### Returns



### hasHook
<!-- @doc-source: HooksManager.hasHook -->
Check if a hook exists

#### Parameters

- ``: string $name Hook name
- ``: name string

#### Returns



### getHooks
<!-- @doc-source: HooksManager.getHooks -->
Get all registered hooks

#### Returns



