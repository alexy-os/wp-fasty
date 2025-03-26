# ConfigurationException

<!-- @doc-source: ConfigurationException -->


## Methods

### __construct
<!-- @doc-source: ConfigurationException.__construct -->
Exception thrown when there are configuration errors
/

namespace FastyChild\Core\Exceptions;

class ConfigurationException extends FastyException
{
/**
Constructor

#### Parameters

- ``: string $configKey Configuration key that caused the error
- ``: string $configValue Configuration value that caused the error
- ``: string $message Additional error message
- ``: int $code Error code
- ``: null \Throwable|null $previous Previous exception
- ``: configKey string
- ``: configValue string
- ``: message string
- ``: code int
- ``: previous \Throwable

