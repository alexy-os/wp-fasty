# NotFoundException

<!-- @doc-source: NotFoundException -->


## Methods

### __construct
<!-- @doc-source: NotFoundException.__construct -->
Exception thrown when an entity is not found
/

namespace FastyChild\Core\Exceptions;

class NotFoundException extends FastyException
{
/**
Constructor

#### Parameters

- ``: string $entityType Type of entity that was not found
- ``: string $entityId Identifier of the entity
- ``: string $message Additional error message
- ``: int $code Error code
- ``: null \Throwable|null $previous Previous exception
- ``: entityType string
- ``: entityId string
- ``: message string
- ``: code int
- ``: previous \Throwable

