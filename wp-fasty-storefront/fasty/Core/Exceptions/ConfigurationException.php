<?php
/**
 * Exception thrown when there are configuration errors
 */

namespace FastyChild\Core\Exceptions;

class ConfigurationException extends FastyException
{
    /**
     * Constructor
     *
     * @param string $configKey Configuration key that caused the error
     * @param string $configValue Configuration value that caused the error
     * @param string $message Additional error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $configKey,
        string $configValue,
        string $message = "",
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct('configuration', "$configKey=$configValue", $message, $code, $previous);
    }
} 