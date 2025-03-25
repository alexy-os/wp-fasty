<?php
/**
 * Exception thrown when there are container-related errors
 */

namespace FastyChild\Core\Exceptions;

class ContainerException extends FastyException
{
    /**
     * Constructor
     *
     * @param string $errorType Type of container error
     * @param string $subject Subject of the error (class/method name)
     * @param string $message Additional error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $errorType,
        string $subject,
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct('container_' . $errorType, $subject, $message, $code, $previous);
    }
} 