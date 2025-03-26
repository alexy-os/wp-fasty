<?php
/**
 * Main Application class for FastyChild framework
 * Serves as the core of the framework
 */

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\ConfigurationException;
use FastyChild\Core\Exceptions\NotFoundException;

class Application {
    /**
     * Singleton instance
     * @var self
     */
    private static $instance = null;
    
    /**
     * Dependency container
     * @var Container
     */
    private $container;
    
    /**
     * Parent theme information
     * @var \WP_Theme
     */
    private $parentTheme;
    
    /**
     * Loaded configurations
     * @var array
     */
    private $configs = [];
    
    /**
     * Configuration cache key prefix
     * @var string
     */
    private const CONFIG_CACHE_PREFIX = 'fasty_config_';
    
    /**
     * Configuration cache time (in seconds)
     * 24 hours by default
     * @var int
     */
    private $configCacheTime = Constants::CONFIG_CACHE_TIME;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->container = new Container();
        $this->parentTheme = wp_get_theme()->parent();
        
        // Register self in container
        $this->container->singleton('app', function() {
            return $this;
        });
        
        // Load base configurations
        $this->loadConfigs();
    }
    
    /**
     * Get application instance
     * 
     * @return self
     */
    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load configuration files
     * Uses caching in production environment
     * 
     * @throws ConfigurationException If configuration file is invalid
     */
    private function loadConfigs(): void {
        $configPath = FASTY_CHILD_PATH . '/fasty/config';
        
        // Try to get configurations from cache in production
        $cacheEnabled = !defined('WP_DEBUG') || !WP_DEBUG;
        
        if ($cacheEnabled) {
            $this->loadConfigsFromCache();
        }
        
        if (!is_dir($configPath)) {
            throw new ConfigurationException(
                'config_path',
                $configPath,
                'Configuration directory does not exist'
            );
        }
        
        $files = glob($configPath . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            
            // Skip if we already have this config from cache
            if ($cacheEnabled && isset($this->configs[$key])) {
                continue;
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
            
            $this->configs[$key] = $this->sanitizeConfigArray($config);
        }
        
        // Cache configurations in production
        if ($cacheEnabled) {
            $this->cacheConfigs();
        }
    }
    
    /**
     * Load configurations from cache
     */
    private function loadConfigsFromCache(): void {
        $configPath = FASTY_CHILD_PATH . '/fasty/config';
        
        if (!is_dir($configPath)) {
            return; // No config directory
        }
        
        $files = glob($configPath . '/*.php');
        
        // Get config keys from file names
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $cached = get_transient(self::CONFIG_CACHE_PREFIX . $key);
            
            if (false !== $cached) {
                $this->configs[$key] = $cached;
                // Debug log
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[" . FASTY_LOG_PREFIX . "DEBUG] Loaded cached config for {$key}");
                }
            }
        }
    }
    
    /**
     * Cache configurations
     */
    private function cacheConfigs(): void {
        foreach ($this->configs as $key => $config) {
            // Make sure we can serialize the configuration (no closures)
            $cacheable = true;
            try {
                // Test if config can be serialized
                serialize($config);
            } catch (\Throwable $e) {
                $cacheable = false;
                error_log("[" . FASTY_LOG_PREFIX . "WARNING] Cannot cache configuration '{$key}': " . $e->getMessage());
            }
            
            if ($cacheable) {
                set_transient(
                    self::CONFIG_CACHE_PREFIX . $key,
                    $config,
                    $this->configCacheTime
                );
            }
        }
    }
    
    /**
     * Sanitize configuration array values recursively
     * 
     * @param mixed $config Configuration to sanitize
     * @return mixed Sanitized configuration
     */
    private function sanitizeConfigArray($config) {
        if (!is_array($config)) {
            if (is_string($config)) {
                return Utils::sanitizeHtml($config);
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
                $result[$key] = $this->sanitizeConfigArray($value);
            } elseif (is_string($value)) {
                $result[$key] = Utils::sanitizeHtml($value);
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
    private function sanitizeConfigValue(string $value): string {
        return Utils::sanitizeHtml($value);
    }
    
    /**
     * Get config value by key
     * 
     * @param string $key Config key in format file.option
     * @param mixed $default Default value if key not found
     * @return mixed
     * @throws ConfigurationException If configuration key format is invalid
     */
    public function config(string $key, $default = null) {
        $parts = explode('.', $key);
        
        if (count($parts) < 1) {
            throw new ConfigurationException(
                'config_key',
                $key,
                'Invalid configuration key format'
            );
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
     * Invalidate configuration cache
     * 
     * @return self
     */
    public function invalidateConfigCache(): self {
        foreach (array_keys($this->configs) as $key) {
            delete_transient(self::CONFIG_CACHE_PREFIX . $key);
        }
        $this->loadConfigs();
        return $this;
    }
    
    /**
     * Get dependency container
     * 
     * @return Container
     */
    public function getContainer(): Container {
        return $this->container;
    }
    
    /**
     * Get parent theme information
     * 
     * @return \WP_Theme
     */
    public function getParentTheme(): \WP_Theme {
        return $this->parentTheme;
    }
    
    /**
     * Helper method to check if we're on a specific admin page
     * 
     * @param string $page Admin page to check
     * @return bool
     */
    public function isAdminPage(string $page): bool {
        if (!is_admin()) {
            return false;
        }
        
        global $pagenow;
        return $pagenow === sanitize_text_field($page);
    }
    
    /**
     * Get a service from the container
     * 
     * @param string $service Service identifier
     * @param bool $required Whether the service is required
     * @return mixed The resolved service
     * @throws NotFoundException When a required service is not found
     */
    public function service(string $service, bool $required = true) {
        if ($this->container->has($service)) {
            return $this->container->get($service);
        }
        
        if ($required) {
            throw new NotFoundException(
                'service',
                $service,
                'Service not registered in container'
            );
        }
        
        return null;
    }
    
    /**
     * Check if a service exists in the container
     * 
     * @param string $service Service identifier
     * @return bool
     */
    public function hasService(string $service): bool {
        return $this->container->has($service);
    }
} 