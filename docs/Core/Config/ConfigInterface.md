# ConfigInterface

<!-- @doc-source: ConfigInterface -->


## Methods

### get
<!-- @doc-source: ConfigInterface.get -->
Interface for configuration manager
/
interface ConfigInterface
{
/**
Get a configuration value

#### Parameters

- ``: string $key Configuration key (dot notation)
- ``: mixed $default Default value if key not found
- ``: key string
- ``: default mixed

#### Returns



### set
<!-- @doc-source: ConfigInterface.set -->
Set a configuration value

#### Parameters

- ``: string $key Configuration key (dot notation)
- ``: mixed $value Configuration value
- ``: key string
- ``: value mixed

#### Returns



### has
<!-- @doc-source: ConfigInterface.has -->
Check if a configuration key exists

#### Parameters

- ``: string $key Configuration key (dot notation)
- ``: key string

#### Returns



### load
<!-- @doc-source: ConfigInterface.load -->
Load configuration from file

#### Parameters

- ``: string $file Configuration file path
- ``: file string

#### Returns



### saveCache
<!-- @doc-source: ConfigInterface.saveCache -->
Save configuration to cache

#### Returns



### loadCache
<!-- @doc-source: ConfigInterface.loadCache -->
Load configuration from cache

#### Returns



### clearCache
<!-- @doc-source: ConfigInterface.clearCache -->
Clear configuration cache

#### Returns



