<?php
/**
 * Exception thrown when an entity is not found
 */

namespace FastyChild\Core\Exceptions;

class NotFoundException extends FastyException
{
    /**
     * Constructor
     *
     * @param string $entityType Type of entity that was not found
     * @param string $entityId Identifier of the entity
     * @param string $message Additional error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $entityType,
        string $entityId,
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($entityType, $entityId, $message, $code, $previous);
    }
} 