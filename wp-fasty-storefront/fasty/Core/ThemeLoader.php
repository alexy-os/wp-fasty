<?php
/**
 * Theme Loader - main entry point for the FastyChild framework
 * Manages service providers and bootstraps the application
 */

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\NotFoundException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Hooks\Constants;

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
     * @var array<string, ServiceProvider>
     */
    private $providers = [];
    
    /**
     * Deferred service providers
     * @var array<string, array>
     */
    private $deferredProviders = [];
    
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
     * @param bool $deferred Whether to defer loading until needed
     * @return self Fluent interface
     * @throws ContainerException If provider class is invalid
     */
    public function registerProvider(string $providerClass, bool $deferred = false): self {
        // Validate provider class exists and implements ServiceProvider
        if (!class_exists($providerClass)) {
            throw new ContainerException(
                'provider_not_found',
                $providerClass,
                'Provider class does not exist'
            );
        }
        
        // Validate implements ServiceProvider interface
        if (!in_array(ServiceProvider::class, class_implements($providerClass) ?: [])) {
            throw new ContainerException(
                'invalid_provider',
                $providerClass,
                sprintf('Provider class must implement %s interface', ServiceProvider::class)
            );
        }
        
        // Skip if already registered
        if (isset($this->providers[$providerClass])) {
            return $this;
        }
        
        try {
            if ($deferred) {
                // Store for later instantiation
                $this->deferredProviders[$providerClass] = [
                    'class' => $providerClass,
                    'provides' => $this->getProvidedServices($providerClass)
                ];
            } else {
                // Instantiate and register immediately
                $provider = $this->app->getContainer()->make($providerClass);
                $this->providers[$providerClass] = $provider;
                
                if (method_exists($provider, 'register')) {
                    $provider->register();
                }
            }
            
            return $this;
        } catch (\Throwable $e) {
            throw new ContainerException(
                'provider_registration',
                $providerClass,
                sprintf('Failed to register provider: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
    
    /**
     * Get services provided by a deferred service provider
     * 
     * @param string $providerClass Provider class name
     * @return array Array of provided service names
     */
    private function getProvidedServices(string $providerClass): array {
        try {
            // Check if class implements ServiceProvider interface
            if (!in_array(ServiceProvider::class, class_implements($providerClass) ?: [])) {
                return [];
            }
            
            $reflection = new \ReflectionClass($providerClass);
            
            // Check if provides method exists
            if (!$reflection->hasMethod('provides')) {
                return [];
            }
            
            // Get the provides method
            $method = $reflection->getMethod('provides');
            
            // If method is static, we can call it directly
            if ($method->isStatic()) {
                return (array) $providerClass::provides();
            }
            
            // Otherwise, we need to create an instance
            // We'll use a dummy instance without calling the constructor
            // This is safe since provides() should not depend on constructor state
            $provider = $reflection->newInstanceWithoutConstructor();
            
            // Ensure the provider inherits from AbstractServiceProvider
            // to avoid calling provides() on incomplete instances
            if ($provider instanceof AbstractServiceProvider) {
                return (array) $provider->provides();
            }
            
            // If provider does not inherit from AbstractServiceProvider,
            // it's safer to assume it doesn't provide any deferred services
            return [];
        } catch (\Throwable $e) {
            // If any error occurs, return empty array
            error_log(sprintf(
                "[%sFATAL] Failed to get provided services from %s: %s",
                FASTY_PREFIX,
                $providerClass,
                $e->getMessage()
            ));
            
            return [];
        }
    }
    
    /**
     * Register multiple service providers
     * 
     * @param array $providers Array of service provider class names
     * @param bool $deferred Whether to defer loading until needed
     * @return self Fluent interface
     */
    public function registerProviders(array $providers, bool $deferred = false): self {
        foreach ($providers as $provider) {
            $this->registerProvider($provider, $deferred);
        }
        return $this;
    }
    
    /**
     * Load a deferred provider
     * 
     * @param string $service Service name
     * @return bool Whether provider was loaded
     */
    private function loadDeferredProvider(string $service): bool {
        foreach ($this->deferredProviders as $providerClass => $info) {
            if (in_array($service, $info['provides'])) {
                $this->registerProvider($providerClass, false);
                unset($this->deferredProviders[$providerClass]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Boot all registered service providers
     * 
     * @return self Fluent interface
     * @throws ContainerException If provider boot fails
     */
    public function boot(): self {
        if ($this->booted) {
            return $this;
        }
        
        // Boot all non-deferred providers
        foreach ($this->providers as $providerClass => $provider) {
            try {
                if (method_exists($provider, 'boot')) {
                    $provider->boot();
                }
            } catch (\Throwable $e) {
                throw new ContainerException(
                    'provider_boot',
                    $providerClass,
                    sprintf('Failed to boot provider: %s', $e->getMessage()),
                    0,
                    $e
                );
            }
        }
        
        $this->booted = true;
        
        // Fire action for possible extension after all providers are loaded
        do_action(Constants::HOOK_FASTY_FRAMEWORK_LOADED, $this);
        
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
    
    /**
     * Get all registered providers
     * 
     * @return array<string, ServiceProvider>
     */
    public function getProviders(): array {
        return $this->providers;
    }
    
    /**
     * Get all deferred providers
     * 
     * @return array<string, array>
     */
    public function getDeferredProviders(): array {
        return $this->deferredProviders;
    }
    
    /**
     * Check if framework is booted
     * 
     * @return bool
     */
    public function isBooted(): bool {
        return $this->booted;
    }
} 