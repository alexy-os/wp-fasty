<?php
/**
 * Base exception class for FastyChild framework
 */

namespace FastyChild\Core\Exceptions;

class FastyException extends \Exception
{
    /**
     * @var string The type of entity that caused the exception
     */
    protected $entityType;

    /**
     * @var string The identifier of the entity
     */
    protected $entityId;

    /**
     * Constructor
     *
     * @param string $entityType Type of entity that caused the exception
     * @param string $entityId Identifier of the entity
     * @param string $message Error message
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
        $this->entityType = $entityType;
        $this->entityId = $entityId;

        $fullMessage = sprintf(
            '[%s] %s: %s',
            $entityType,
            $entityId,
            $message
        );

        parent::__construct($fullMessage, $code, $previous);
    }

    /**
     * Get entity type
     *
     * @return string
     */
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Get entity identifier
     *
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }
} 