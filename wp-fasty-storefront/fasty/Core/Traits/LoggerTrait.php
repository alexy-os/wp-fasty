<?php
/**
 * Logger Trait
 * Provides logging functionality for classes
 */

namespace FastyChild\Core\Traits;

trait LoggerTrait
{
    /**
     * Log a message
     * 
     * @param string $message Message to log
     * @param string $level Log level (info, warning, error)
     * @param array $context Additional context data
     * @return void
     */
    protected function log(string $message, string $level = 'info', array $context = []): void
    {
        // Определяем префикс для логов
        $prefix = defined('FASTY_LOG_PREFIX') ? FASTY_LOG_PREFIX : 'fasty_';
        
        // Преобразуем контекст в JSON если он не пустой
        $contextString = !empty($context) ? ' ' . json_encode($context) : '';
        
        // Создаем сообщение для лога
        $logMessage = "[{$prefix}{$level}] {$message}{$contextString}";
        
        // В зависимости от уровня используем разные функции логирования
        switch ($level) {
            case 'error':
                error_log($logMessage);
                break;
            case 'warning':
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log($logMessage);
                }
                break;
            case 'info':
            default:
                if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                    error_log($logMessage);
                }
                break;
        }
        
        // Добавляем хук для возможности внешней обработки логов
        do_action('fasty_log', $message, $level, $context);
    }
    
    /**
     * Log an info message
     * 
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function logInfo(string $message, array $context = []): void
    {
        $this->log($message, 'info', $context);
    }
    
    /**
     * Log a warning message
     * 
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function logWarning(string $message, array $context = []): void
    {
        $this->log($message, 'warning', $context);
    }
    
    /**
     * Log an error message
     * 
     * @param string $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $this->log($message, 'error', $context);
    }
} 