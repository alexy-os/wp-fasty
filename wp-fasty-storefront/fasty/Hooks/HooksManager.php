<?php

namespace FastyChild\Hooks;

use FastyChild\Core\Container;
use FastyChild\Core\Traits\LoggerTrait;

class HooksManager
{
    use LoggerTrait;
    
    /**
     * Container instance
     * 
     * @var Container
     */
    private $container;
    
    /**
     * Registered hooks
     * 
     * @var array
     */
    private $hooks = [];
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Add a hook implementation
     * 
     * @param string $name
     * @param string|HookInterface $hook
     * @return self
     */
    public function addHook(string $name, $hook): self
    {
        $this->hooks[$name] = $hook;
        $this->logInfo("Hook '{$name}' added", ['class' => is_string($hook) ? $hook : get_class($hook)]);
        return $this;
    }
    
    /**
     * Register all hooks that can be registered
     * 
     * @return void
     */
    public function registerHooks(): void
    {
        $this->logInfo("Starting hooks registration");
        $registeredCount = 0;
        
        foreach ($this->hooks as $name => $hook) {
            try {
                // Если это строка, создаем сервис в контейнере
                if (is_string($hook)) {
                    // Регистрируем хук как сервис в контейнере, если еще не зарегистрирован
                    if (!$this->container->has('hooks.' . $name)) {
                        $this->container->singleton('hooks.' . $name, function ($container) use ($hook) {
                            return new $hook($container);
                        });
                    }
                    
                    $hookInstance = $this->container->get('hooks.' . $name);
                } else {
                    $hookInstance = $hook;
                }
                
                // Убедимся, что экземпляр реализует интерфейс HookInterface
                if (!($hookInstance instanceof HookInterface)) {
                    throw new \RuntimeException("Hook '$name' does not implement HookInterface");
                }
                
                if ($hookInstance->canRegister()) {
                    $hookInstance->register();
                    $registeredCount++;
                    $this->logInfo("Hook '{$name}' registered successfully");
                } else {
                    $this->logInfo("Hook '{$name}' skipped (canRegister returned false)");
                }
            } catch (\Throwable $e) {
                // Используем наш новый метод логирования ошибок
                $this->logError("Error registering hook '{$name}': " . $e->getMessage());
                
                // В режиме разработки можно выводить ошибку
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    throw $e;
                }
            }
        }
        
        $this->logInfo("Hooks registration completed", ['registered' => $registeredCount, 'total' => count($this->hooks)]);
    }
} 