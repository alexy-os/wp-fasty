# ContainerException

<!-- @doc-source: ContainerException -->


## Methods

### __construct
<!-- @doc-source: ContainerException.__construct -->
Exception thrown when there are container-related errors
/

namespace FastyChild\Core\Exceptions;

class ContainerException extends FastyException
{
/**
Constructor

#### Parameters

- ``: string $errorType Type of container error
- ``: string $subject Subject of the error (class/method name)
- ``: string $message Additional error message
- ``: int $code Error code
- ``: null \Throwable|null $previous Previous exception
- ``: errorType string
- ``: subject string
- ``: message string
- ``: code int
- ``: previous \Throwable

