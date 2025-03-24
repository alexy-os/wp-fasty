<?php
/**
 * Main Application class for FastyChild framework
 * Serves as the core of the framework
 */

namespace FastyChild\Core;

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
     */
    private function loadConfigs(): void {
        $configPath = FASTY_CHILD_PATH . '/fasty/config';
        
        if (is_dir($configPath)) {
            $files = glob($configPath . '/*.php');
            
            foreach ($files as $file) {
                $key = basename($file, '.php');
                $this->configs[$key] = include $file;
            }
        }
    }
    
    /**
     * Get config value by key
     * 
     * @param string $key Config key in format file.option
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function config(string $key, $default = null) {
        $parts = explode('.', $key);
        $file = $parts[0];
        
        if (!isset($this->configs[$file])) {
            return $default;
        }
        
        $config = $this->configs[$file];
        
        // If nested key is requested (e.g. 'app.debug')
        if (count($parts) > 1) {
            for ($i = 1; $i < count($parts); $i++) {
                if (!isset($config[$parts[$i]])) {
                    return $default;
                }
                $config = $config[$parts[$i]];
            }
        }
        
        return $config;
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
    public function getParentTheme() {
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
        return $pagenow === $page;
    }
} 