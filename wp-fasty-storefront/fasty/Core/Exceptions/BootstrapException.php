<?php
/**
 * Bootstrap Exception Class
 * Represents exceptions that occur during the bootstrap process
 */

namespace FastyChild\Core\Exceptions;

class BootstrapException extends FrameworkException
{
    /**
     * Constructor
     * 
     * @param string $message Error message
     * @param int $code Error code (defaults to 0)
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get formatted string representation of the exception
     * 
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}";
    }
    
    /**
     * Get safe version of the exception message with sensitive data removed
     * 
     * @return string
     */
    public function getSafeMessage(): string
    {
        // Strip potential sensitive information from message
        return wp_kses(
            $this->message,
            [
                'em' => [],
                'strong' => [],
                'code' => [],
            ]
        );
    }
} 