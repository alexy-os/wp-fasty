# ThemeLoader

<!-- @doc-source: ThemeLoader -->


## Methods

### __construct
<!-- @doc-source: ThemeLoader.__construct -->
Theme Loader - main entry point for the FastyChild framework
Manages service providers and bootstraps the application
/

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\NotFoundException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Hooks\Constants;

class ThemeLoader {
/**
Singleton instance

### init
<!-- @doc-source: ThemeLoader.init -->
Initialize theme loader

#### Returns



### registerProvider
<!-- @doc-source: ThemeLoader.registerProvider -->
Register a service provider

#### Parameters

- ``: string $providerClass Service provider class name
- ``: bool $deferred Whether to defer loading until needed
- ``: providerClass string
- ``: deferred bool

#### Returns



### getProvidedServices
<!-- @doc-source: ThemeLoader.getProvidedServices -->
Get services provided by a deferred service provider

#### Parameters

- ``: string $providerClass Provider class name
- ``: providerClass string

#### Returns



### registerProviders
<!-- @doc-source: ThemeLoader.registerProviders -->
Register multiple service providers

#### Parameters

- ``: array $providers Array of service provider class names
- ``: bool $deferred Whether to defer loading until needed
- ``: providers array
- ``: deferred bool

#### Returns



### loadDeferredProvider
<!-- @doc-source: ThemeLoader.loadDeferredProvider -->
Load a deferred provider

#### Parameters

- ``: string $service Service name
- ``: service string

#### Returns



### boot
<!-- @doc-source: ThemeLoader.boot -->
Boot all registered service providers

#### Returns



### getApplication
<!-- @doc-source: ThemeLoader.getApplication -->
Get application instance

#### Returns



### getContainer
<!-- @doc-source: ThemeLoader.getContainer -->
Get application container

#### Returns



### getProviders
<!-- @doc-source: ThemeLoader.getProviders -->
Get all registered providers

#### Returns



### getDeferredProviders
<!-- @doc-source: ThemeLoader.getDeferredProviders -->
Get all deferred providers

#### Returns



### isBooted
<!-- @doc-source: ThemeLoader.isBooted -->
Check if framework is booted

#### Returns



