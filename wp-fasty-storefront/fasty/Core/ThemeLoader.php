<?php
/**
 * Theme Loader - main entry point for the FastyChild framework
 * Manages service providers and bootstraps the application
 */

namespace FastyChild\Core;

class ThemeLoader {
    /**
     * Singleton instance
     * @var self
     */
    private static $instance = null;
    
    /**
     * Application instance
     * @var Application
     */
    private $app;
    
    /**
     * Registered service providers
     * @var array
     */
    private $providers = [];
    
    /**
     * Flag to track if framework is booted
     * @var bool
     */
    private $booted = false;
    
    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->app = Application::getInstance();
    }
    
    /**
     * Initialize theme loader
     * 
     * @return self
     */
    public static function init(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register a service provider
     * 
     * @param string $providerClass Service provider class name
     * @return self Fluent interface
     */
    public function registerProvider(string $providerClass): self {
        if (!in_array($providerClass, $this->providers)) {
            $this->providers[] = $providerClass;
        }
        return $this;
    }
    
    /**
     * Boot all registered service providers
     * 
     * @return self Fluent interface
     */
    public function boot(): self {
        if ($this->booted) {
            return $this;
        }
        
        $container = $this->app->getContainer();
        
        // Initialize providers
        foreach ($this->providers as $providerClass) {
            if (!class_exists($providerClass)) {
                error_log("Service provider class not found: {$providerClass}");
                continue;
            }
            
            $provider = new $providerClass($container);
            
            // Register services
            if (method_exists($provider, 'register')) {
                $provider->register();
            }
            
            // Register provider in container for later access
            $container->bind($providerClass, $provider);
        }
        
        // Boot providers (after all dependencies are registered)
        foreach ($this->providers as $providerClass) {
            $provider = $container->get($providerClass);
            
            // Start services
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
        
        $this->booted = true;
        
        // Fire action for possible extension after all providers are loaded
        do_action('fasty_child_booted', $this);
        
        return $this;
    }
    
    /**
     * Get application instance
     * 
     * @return Application
     */
    public function getApplication(): Application {
        return $this->app;
    }
    
    /**
     * Get application container
     * 
     * @return Container
     */
    public function getContainer(): Container {
        return $this->app->getContainer();
    }
} 