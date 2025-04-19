<?php

declare(strict_types=1);

/**
 * Application class for WP FastY Theme
 *
 * Main application entry point with singleton pattern.
 * Responsible for service registration and bootstrapping.
 *
 * @package WPFasty\Core
 */

namespace WPFasty\Core;

use WPFasty\Core\Container;
use WPFasty\Core\LoggerInterface;

/**
 * Application singleton class
 */
class Application
{
    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static $instance = null;
    
    /**
     * DI Container
     *
     * @var Container
     */
    private $container;
    
    /**
     * Logger instance
     *
     * @var LoggerInterface|null
     */
    private $logger;
    
    /**
     * Configuration
     *
     * @var array<string, mixed>
     */
    private $config;

    /**
     * Private constructor to prevent direct instantiation
     *
     * @param array<string, mixed> $config Configuration options
     */
    private function __construct(array $config = [])
    {
        $this->config = $config;
        $this->container = new Container();
        
        // If logger is provided in config, use it
        if (isset($config['logger']) && $config['logger'] instanceof LoggerInterface) {
            $this->logger = $config['logger'];
        }
        
        $this->loadServices();
        $this->bootServices();
    }

    /**
     * Get singleton instance
     *
     * @param array<string, mixed> $config Configuration options
     * @return self Instance of Application
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Load services from configuration file
     */
    private function loadServices(): void
    {
        // Get services file path from config or use default
        $servicesFile = $this->config['services_file'] ?? null;
        
        // If no specific path provided, try common locations
        if ($servicesFile === null) {
            $possiblePaths = [
                // Current directory
                'services.php',
                // Configs subdirectory
                'configs/services.php',
                // Relative to Core directory
                dirname(__DIR__) . '/configs/services.php',
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $servicesFile = $path;
                    break;
                }
            }
        }
        
        // Load services if file exists
        if ($servicesFile !== null && file_exists($servicesFile)) {
            $serviceLoader = require $servicesFile;
            if (is_callable($serviceLoader)) {
                $serviceLoader($this->container);
            }
        }
        
        // Auto-tag the bootable services
        $this->tagBootableServices();
    }
    
    /**
     * Tag services that implement BootableServiceInterface or have a register method
     */
    private function tagBootableServices(): void
    {
        foreach ($this->container->getServiceIds() as $serviceId) {
            try {
                // Skip if already tagged
                if ($this->container->hasTag($serviceId, ContainerInterface::TAG_BOOTABLE)) {
                    continue;
                }
                
                $className = $this->container->getServiceClass($serviceId);
                
                // If no class name, skip
                if (empty($className)) {
                    continue;
                }
                
                // Check if class implements BootableServiceInterface
                if (is_a($className, BootableServiceInterface::class, true)) {
                    $this->container->addTag($serviceId, ContainerInterface::TAG_BOOTABLE, ['interface' => true]);
                    continue;
                }
                
                // Check if class has a register method
                if (method_exists($className, 'register')) {
                    $this->container->addTag($serviceId, ContainerInterface::TAG_BOOTABLE, ['register' => true]);
                }
            } catch (\Exception $e) {
                $this->logError("Error checking bootable status for service '{$serviceId}': " . $e->getMessage());
            }
        }
    }

    /**
     * Boot all registered services
     */
    private function bootServices(): void
    {
        // Compile the container for optimized performance (if possible)
        $this->container->compile();
        
        // Get all bootable services
        $bootableServices = $this->container->findTaggedServiceIds(ContainerInterface::TAG_BOOTABLE);
        
        // Boot each service
        foreach ($bootableServices as $serviceId) {
            try {
                $instance = $this->container->get($serviceId);
                
                if ($instance instanceof BootableServiceInterface) {
                    $instance->boot();
                } elseif (method_exists($instance, 'register')) {
                    $instance->register();
                }
            } catch (\Exception $e) {
                // Log error but continue with other services
                $this->logError("Error booting service '{$serviceId}': " . $e->getMessage());
            }
        }
    }

    /**
     * Get the container instance
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
    
    /**
     * Get a service from the container
     *
     * @param string $service Service identifier
     * @return mixed The service instance
     */
    public function get(string $service)
    {
        return $this->container->get($service);
    }
    
    /**
     * Log an error message
     *
     * @param string $message Error message
     * @param array<string, mixed> $context Additional context data
     */
    private function logError(string $message, array $context = []): void
    {
        if ($this->logger !== null) {
            $this->logger->error($message, $context);
        } else {
            // Fallback to error_log but only in development
            if (isset($this->config['environment']) && $this->config['environment'] === 'development') {
                error_log('[ERROR] ' . $message);
            }
        }
    }
    
    /**
     * Set a logger instance
     *
     * @param LoggerInterface $logger Logger implementation
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
