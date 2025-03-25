<?php
declare(strict_types=1);

namespace FastyChild\Core\Config;

/**
 * Interface for configuration manager
 */
interface ConfigInterface
{
    /**
     * Get a configuration value
     *
     * @param string $key Configuration key (dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function get(string $key, $default = null);
    
    /**
     * Set a configuration value
     *
     * @param string $key Configuration key (dot notation)
     * @param mixed $value Configuration value
     * @return void
     */
    public function set(string $key, $value): void;
    
    /**
     * Check if a configuration key exists
     *
     * @param string $key Configuration key (dot notation)
     * @return bool True if key exists
     */
    public function has(string $key): bool;
    
    /**
     * Load configuration from file
     *
     * @param string $file Configuration file path
     * @return array<string, mixed> Loaded configuration
     */
    public function load(string $file): array;
    
    /**
     * Save configuration to cache
     *
     * @return bool True if saved successfully
     */
    public function saveCache(): bool;
    
    /**
     * Load configuration from cache
     *
     * @return bool True if loaded successfully
     */
    public function loadCache(): bool;
    
    /**
     * Clear configuration cache
     *
     * @return bool True if cleared successfully
     */
    public function clearCache(): bool;
} 