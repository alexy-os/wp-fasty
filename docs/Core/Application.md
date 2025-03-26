# Application

<!-- @doc-source: Application -->


## Methods

### __construct
<!-- @doc-source: Application.__construct -->
Main Application class for FastyChild framework
Serves as the core of the framework
/

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\ConfigurationException;
use FastyChild\Core\Exceptions\NotFoundException;

class Application {
/**
Singleton instance

### getInstance
<!-- @doc-source: Application.getInstance -->
Get application instance

#### Returns



### loadConfigs
<!-- @doc-source: Application.loadConfigs -->
Load configuration files
Uses caching in production environment

#### Returns



### loadConfigsFromCache
<!-- @doc-source: Application.loadConfigsFromCache -->
Load configurations from cache

#### Returns



### cacheConfigs
<!-- @doc-source: Application.cacheConfigs -->
Cache configurations

#### Returns



### sanitizeConfigArray
<!-- @doc-source: Application.sanitizeConfigArray -->
Sanitize configuration array values recursively

#### Parameters

- ``: mixed $config Configuration to sanitize
- ``: config mixed

#### Returns



### sanitizeConfigValue
<!-- @doc-source: Application.sanitizeConfigValue -->
Sanitize a configuration value

#### Parameters

- ``: string $value Value to sanitize
- ``: value string

#### Returns



### config
<!-- @doc-source: Application.config -->
Get config value by key

#### Parameters

- ``: string $key Config key in format file.option
- ``: mixed $default Default value if key not found
- ``: key string
- ``: default mixed

#### Returns



### invalidateConfigCache
<!-- @doc-source: Application.invalidateConfigCache -->
Invalidate configuration cache

#### Returns



### getContainer
<!-- @doc-source: Application.getContainer -->
Get dependency container

#### Returns



### getParentTheme
<!-- @doc-source: Application.getParentTheme -->
Get parent theme information

#### Returns



### isAdminPage
<!-- @doc-source: Application.isAdminPage -->
Helper method to check if we're on a specific admin page

#### Parameters

- ``: string $page Admin page to check
- ``: page string

#### Returns



### service
<!-- @doc-source: Application.service -->
Get a service from the container

#### Parameters

- ``: string $service Service identifier
- ``: bool $required Whether the service is required
- ``: service string
- ``: required bool

#### Returns



### hasService
<!-- @doc-source: Application.hasService -->
Check if a service exists in the container

#### Parameters

- ``: string $service Service identifier
- ``: service string

#### Returns



