# HookInterface

<!-- @doc-source: HookInterface -->


## Methods

### __construct
<!-- @doc-source: HookInterface.__construct -->
Interface for WordPress hook implementations
/
interface HookInterface
{
/**
Constructor

#### Parameters

- ``: Container $container Service container
- ``: container Container

### register
<!-- @doc-source: HookInterface.register -->
Register hooks with WordPress

#### Returns



### canRegister
<!-- @doc-source: HookInterface.canRegister -->
Determine if hooks should be registered

#### Returns



### getIdentifier
<!-- @doc-source: HookInterface.getIdentifier -->
Get hook identifier

#### Returns



### getPriority
<!-- @doc-source: HookInterface.getPriority -->
Get priority for hook execution

#### Returns



