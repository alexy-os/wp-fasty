<?php
declare(strict_types=1);

namespace FastyChild\Core\Hooks;

use FastyChild\Core\Container;
use FastyChild\Core\Traits\LoggerTrait;

/**
 * Manages WordPress hook implementations
 */
class HooksManager
{
    use LoggerTrait;
    
    /**
     * Service container
     * @var Container
     */
    private Container $container;
    
    /**
     * Registered hook implementations
     * @var array<string, HookInterface>
     */
    private array $hooks = [];
    
    /**
     * Constructor
     *
     * @param Container $container Service container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Add a hook implementation
     *
     * @param string $name Hook name
     * @param string|HookInterface $hookClass Hook class name or instance
     * @return self
     */
    public function addHook(string $name, $hookClass): self
    {
        // If hook is already a HookInterface instance, use it directly
        if ($hookClass instanceof HookInterface) {
            $this->hooks[$name] = $hookClass;
            return $this;
        }
        
        // Create instance if string class name provided
        if (is_string($hookClass) && class_exists($hookClass)) {
            try {
                $hook = $this->container->make($hookClass);
                
                if ($hook instanceof HookInterface) {
                    $this->hooks[$name] = $hook;
                } else {
                    $this->error(sprintf(
                        'Hook class %s does not implement HookInterface',
                        $hookClass
                    ));
                }
            } catch (\Throwable $e) {
                $this->error(sprintf(
                    'Failed to create hook instance %s: %s',
                    $hookClass,
                    $e->getMessage()
                ), ['exception' => $e]);
            }
        } else {
            $this->error(sprintf('Invalid hook class: %s', $hookClass));
        }
        
        return $this;
    }
    
    /**
     * Register all hooks with WordPress
     *
     * @return void
     */
    public function registerHooks(): void
    {
        if (empty($this->hooks)) {
            $this->warning('No hooks registered with HooksManager');
            return;
        }
        
        // Sort hooks by priority
        $sortedHooks = $this->sortHooksByPriority();
        
        foreach ($sortedHooks as $name => $hook) {
            try {
                // Check if hook should be registered
                if ($hook->canRegister()) {
                    $this->debug(sprintf('Registering hook: %s (%s)', $name, get_class($hook)));
                    
                    // Allow monitoring via WordPress action
                    if (function_exists('do_action')) {
                        do_action('fasty_before_register_hook', $hook, $name);
                    }
                    
                    // Register the hook
                    $hook->register();
                    
                    if (function_exists('do_action')) {
                        do_action('fasty_after_register_hook', $hook, $name);
                    }
                } else {
                    $this->debug(sprintf('Skipping hook: %s (canRegister() returned false)', $name));
                }
            } catch (\Throwable $e) {
                $this->error(sprintf(
                    'Error registering hook %s: %s',
                    $name,
                    $e->getMessage()
                ), ['exception' => $e]);
            }
        }
        
        $this->info(sprintf('Registered %d hooks', count($sortedHooks)));
    }
    
    /**
     * Sort hooks by priority
     *
     * @return array<string, HookInterface> Sorted hooks
     */
    private function sortHooksByPriority(): array
    {
        $hooks = $this->hooks;
        
        uasort($hooks, function (HookInterface $a, HookInterface $b) {
            return $a->getPriority() <=> $b->getPriority();
        });
        
        return $hooks;
    }
    
    /**
     * Get a specific hook by name
     *
     * @param string $name Hook name
     * @return HookInterface|null Hook instance or null if not found
     */
    public function getHook(string $name): ?HookInterface
    {
        return $this->hooks[$name] ?? null;
    }
    
    /**
     * Check if a hook exists
     *
     * @param string $name Hook name
     * @return bool True if hook exists
     */
    public function hasHook(string $name): bool
    {
        return isset($this->hooks[$name]);
    }
    
    /**
     * Get all registered hooks
     *
     * @return array<string, HookInterface> All registered hooks
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }
} 