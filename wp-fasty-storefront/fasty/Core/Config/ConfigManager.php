<?php
declare(strict_types=1);

namespace FastyChild\Core\Config;

use FastyChild\Core\Traits\LoggerTrait;
use FastyChild\Core\Utils;
use FastyChild\Core\Exceptions\ConfigurationException;
use FastyChild\Hooks\Constants;

/**
 * Configuration manager
 */
class ConfigManager implements ConfigInterface
{
    use LoggerTrait;
    
    /**
     * Configuration cache key prefix
     */
    private const CACHE_PREFIX = 'fasty_config_';
    
    /**
     * Configuration cache time in seconds
     */
    private const CACHE_TIME = DAY_IN_SECONDS;
    
    /**
     * Configuration data
     * @var array<string, mixed>
     */
    private array $configs = [];
    
    /**
     * Configuration directory
     * @var string
     */
    private string $configDir;
    
    /**
     * Constructor
     *
     * @param string $configDir Configuration directory
     */
    public function __construct(string $configDir)
    {
        $this->configDir = rtrim($configDir, '/\\');
    }
    
    /**
     * Initialize configurations
     *
     * @return self
     */
    public function initialize(): self
    {
        $this->loadAllConfigs();
        
        // Fire configuration loaded action
        if (function_exists('do_action')) {
            do_action(Constants::HOOK_FASTY_CONFIG_LOADED, $this->configs);
        }
        
        return $this;
    }
    
    /**
     * Load all configuration files
     *
     * @return void
     */
    private function loadAllConfigs(): void
    {
        // Try to load from cache first
        if (!Utils::isDevelopmentEnvironment() && $this->loadCache()) {
            $this->debug('Loaded configurations from cache');
            return;
        }
        
        $configFiles = $this->getConfigFiles();
        
        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $this->configs[$key] = $this->load($file);
        }
        
        // Save cache in production
        if (!Utils::isDevelopmentEnvironment()) {
            $this->saveCache();
        }
    }
    
    /**
     * Get configuration files
     *
     * @return array<int, string> Configuration file paths
     */
    private function getConfigFiles(): array
    {
        if (!is_dir($this->configDir)) {
            $this->warning(sprintf('Configuration directory does not exist: %s', $this->configDir));
            return [];
        }
        
        $files = glob($this->configDir . '/*.php');
        
        if (!is_array($files)) {
            $this->warning(sprintf('Failed to list configuration files in: %s', $this->configDir));
            return [];
        }
        
        return $files;
    }
    
    /**
     * Load configuration from file
     *
     * @param string $file Configuration file path
     * @return array<string, mixed> Loaded configuration
     * @throws ConfigurationException If file not found or invalid
     */
    public function load(string $file): array
    {
        if (!file_exists($file)) {
            throw new ConfigurationException(
                'config_file',
                $file,
                'Configuration file does not exist'
            );
        }
        
        if (!is_readable($file)) {
            throw new ConfigurationException(
                'config_file',
                $file,
                'Configuration file is not readable'
            );
        }
        
        $config = include $file;
        
        if (!is_array($config)) {
            throw new ConfigurationException(
                'config_content',
                $file,
                'Configuration file must return an array'
            );
        }
        
        return $this->sanitizeConfig($config);
    }
    
    /**
     * Sanitize configuration array values recursively
     *
     * @param mixed $config Configuration to sanitize
     * @return mixed Sanitized configuration
     */
    private function sanitizeConfig($config)
    {
        if (!is_array($config)) {
            if (is_string($config)) {
                return $this->sanitizeValue($config);
            }
            
            // Don't try to sanitize closures or objects that can't be serialized
            if ($config instanceof \Closure || (is_object($config) && !method_exists($config, '__toString'))) {
                return null;
            }
            
            return $config;
        }
        
        $result = [];
        foreach ($config as $key => $value) {
            // Skip closures and non-serializable objects in configs that will be cached
            if ($value instanceof \Closure || (is_object($value) && !method_exists($value, '__toString'))) {
                continue;
            }
            
            if (is_array($value)) {
                $result[$key] = $this->sanitizeConfig($value);
            } elseif (is_string($value)) {
                $result[$key] = $this->sanitizeValue($value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Sanitize a configuration value
     *
     * @param string $value Value to sanitize
     * @return string Sanitized value
     */
    private function sanitizeValue(string $value): string
    {
        // If value contains HTML, sanitize it
        if (strip_tags($value) !== $value) {
            return Utils::sanitizeHtml($value);
        }
        
        // Otherwise, just sanitize as text
        return sanitize_text_field($value);
    }
    
    /**
     * Get a configuration value
     *
     * @param string $key Configuration key (dot notation)
     * @param mixed $default Default value if key not found
     * @return mixed Configuration value
     */
    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        
        if (count($parts) < 1) {
            return $default;
        }
        
        $file = $parts[0];
        
        if (!isset($this->configs[$file])) {
            return $default;
        }
        
        $config = $this->configs[$file];
        
        // If nested key is requested (e.g. 'app.debug')
        array_shift($parts);
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }
        
        return $config;
    }
    
    /**
     * Set a configuration value
     *
     * @param string $key Configuration key (dot notation)
     * @param mixed $value Configuration value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        
        if (count($parts) < 1) {
            return;
        }
        
        $file = $parts[0];
        
        // Create file config if it doesn't exist
        if (!isset($this->configs[$file])) {
            $this->configs[$file] = [];
        }
        
        // Handle simple file-level setting
        if (count($parts) === 1) {
            $this->configs[$file] = $value;
            return;
        }
        
        // Handle nested keys
        $config = &$this->configs[$file];
        array_shift($parts);
        
        // Navigate to the right depth
        foreach ($parts as $i => $part) {
            // If we're at the last part, set the value
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
                break;
            }
            
            // Otherwise, make sure we can navigate deeper
            if (!isset($config[$part]) || !is_array($config[$part])) {
                $config[$part] = [];
            }
            
            $config = &$config[$part];
        }
    }
    
    /**
     * Check if a configuration key exists
     *
     * @param string $key Configuration key (dot notation)
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        $parts = explode('.', $key);
        
        if (count($parts) < 1) {
            return false;
        }
        
        $file = $parts[0];
        
        if (!isset($this->configs[$file])) {
            return false;
        }
        
        $config = $this->configs[$file];
        
        // If nested key is requested (e.g. 'app.debug')
        array_shift($parts);
        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return false;
            }
            $config = $config[$part];
        }
        
        return true;
    }
    
    /**
     * Save configuration to cache
     *
     * @return bool True if saved successfully
     */
    public function saveCache(): bool
    {
        $success = true;
        
        foreach ($this->configs as $key => $config) {
            // Make sure we can serialize the configuration (no closures)
            try {
                // Test if config can be serialized
                serialize($config);
                $cacheable = true;
            } catch (\Throwable $e) {
                $cacheable = false;
                $this->warning(sprintf('Cannot cache configuration "%s": %s', $key, $e->getMessage()));
                $success = false;
            }
            
            if ($cacheable) {
                set_transient(
                    self::CACHE_PREFIX . $key,
                    $config,
                    self::CACHE_TIME
                );
            }
        }
        
        return $success;
    }
    
    /**
     * Load configuration from cache
     *
     * @return bool True if loaded successfully
     */
    public function loadCache(): bool
    {
        // Get config files first to know what to load
        $configFiles = $this->getConfigFiles();
        $allLoaded = !empty($configFiles);
        
        foreach ($configFiles as $file) {
            $key = basename($file, '.php');
            $cached = get_transient(self::CACHE_PREFIX . $key);
            
            if (false !== $cached) {
                $this->configs[$key] = $cached;
            } else {
                // If any config is not in cache, consider cache incomplete
                $allLoaded = false;
            }
        }
        
        return $allLoaded;
    }
    
    /**
     * Clear configuration cache
     *
     * @return bool True if cleared successfully
     */
    public function clearCache(): bool
    {
        $success = true;
        
        foreach (array_keys($this->configs) as $key) {
            if (!delete_transient(self::CACHE_PREFIX . $key)) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get all configurations
     *
     * @return array<string, mixed> All configurations
     */
    public function all(): array
    {
        return $this->configs;
    }
} 