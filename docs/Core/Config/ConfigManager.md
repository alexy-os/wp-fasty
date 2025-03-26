# ConfigManager

<!-- @doc-source: ConfigManager -->
Configuration manager

## Methods

### __construct
<!-- @doc-source: ConfigManager.__construct -->
Configuration manager
/
class ConfigManager implements ConfigInterface
{
use LoggerTrait;

/**
Configuration cache key prefix
/
private const CACHE_PREFIX = 'fasty_config_';

/**
Configuration cache time in seconds
/
private const CACHE_TIME = DAY_IN_SECONDS;

/**
Configuration data

#### Parameters

- ``: configDir string

### initialize
<!-- @doc-source: ConfigManager.initialize -->
Initialize configurations

#### Returns



### loadAllConfigs
<!-- @doc-source: ConfigManager.loadAllConfigs -->
Load all configuration files

#### Returns



### getConfigFiles
<!-- @doc-source: ConfigManager.getConfigFiles -->
Get configuration files

#### Returns



### load
<!-- @doc-source: ConfigManager.load -->
Load configuration from file

#### Parameters

- ``: string $file Configuration file path
- ``: file string

#### Returns



### sanitizeConfig
<!-- @doc-source: ConfigManager.sanitizeConfig -->
Sanitize configuration array values recursively

#### Parameters

- ``: mixed $config Configuration to sanitize
- ``: config mixed

#### Returns



### sanitizeValue
<!-- @doc-source: ConfigManager.sanitizeValue -->
Sanitize a configuration value

#### Parameters

- ``: string $value Value to sanitize
- ``: value string

#### Returns



### get
<!-- @doc-source: ConfigManager.get -->
Get a configuration value

#### Parameters

- ``: string $key Configuration key (dot notation)
- ``: mixed $default Default value if key not found
- ``: key string
- ``: default mixed

#### Returns



### set
<!-- @doc-source: ConfigManager.set -->
Set a configuration value

#### Parameters

- ``: string $key Configuration key (dot notation)
- ``: mixed $value Configuration value
- ``: key string
- ``: value mixed

#### Returns



### has
<!-- @doc-source: ConfigManager.has -->
Check if a configuration key exists

#### Parameters

- ``: string $key Configuration key (dot notation)
- ``: key string

#### Returns



### saveCache
<!-- @doc-source: ConfigManager.saveCache -->
Save configuration to cache

#### Returns



### loadCache
<!-- @doc-source: ConfigManager.loadCache -->
Load configuration from cache

#### Returns



### clearCache
<!-- @doc-source: ConfigManager.clearCache -->
Clear configuration cache

#### Returns



### all
<!-- @doc-source: ConfigManager.all -->
Get all configurations

#### Returns



