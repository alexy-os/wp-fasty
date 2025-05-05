<?php

declare(strict_types=1);

/**
 * Container class for dependency injection
 *
 * This class implements the ContainerInterface and provides methods for binding services,
 * resolving dependencies, and managing service instances.
 *
 * @package WPFasty\Core
 */

namespace WPFasty\Core;

class Container implements ContainerInterface
{
    /**
     * Service bindings
     *
     * @var array<string, mixed>
     */
    private $bindings = [];

    /**
     * Service instances
     *
     * @var array<string, mixed>
     */
    private $instances = [];

    /**
     * Service factories
     *
     * @var array<string, callable>
     */
    private $factories = [];

    /**
     * Service class mappings
     *
     * @var array<string, string>
     */
    private $classMap = [];

    /**
     * Service tags
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    private $tags = [];

    /**
     * {@inheritdoc}
     */
    public function bind(string $abstract, $concrete, ?string $className = null): void
    {
        $this->bindings[$abstract] = $concrete;

        if ($concrete instanceof \Closure) {
            $this->factories[$abstract] = $concrete;
        }

        if ($className !== null) {
            $this->classMap[$abstract] = $className;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function singleton(string $abstract, $concrete, ?string $className = null): void
    {
        $this->factories[$abstract] = $concrete;

        $this->bindings[$abstract] = function ($container) use ($concrete, $abstract) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete($container);
            }
            return $this->instances[$abstract];
        };

        if ($className !== null) {
            $this->classMap[$abstract] = $className;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $abstract)
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \Exception("No binding found for {$abstract}");
        }

        $concrete = $this->bindings[$abstract];

        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        return $concrete;
    }

    /**
     * Get the factory for a service
     *
     * @param string $abstract Service ID
     * @return callable|null The factory closure or null if not found
     */
    public function getFactory(string $abstract): ?callable
    {
        return $this->factories[$abstract] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceIds(): array
    {
        return array_keys($this->bindings);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTag(string $serviceId, string $tag): bool
    {
        return isset($this->tags[$serviceId][$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function addTag(string $serviceId, string $tag, array $attributes = []): void
    {
        $this->tags[$serviceId][$tag] = $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function findTaggedServiceIds(string $tag): array
    {
        $services = [];

        foreach ($this->tags as $serviceId => $tags) {
            if (isset($tags[$tag])) {
                $services[] = $serviceId;
            }
        }

        return $services;
    }

    /**
     * Get the class name for a service ID
     *
     * @param string $serviceId Service ID
     * @return string|null Class name or null if not mapped
     */
    public function getClassName(string $serviceId): ?string
    {
        return $this->classMap[$serviceId] ?? null;
    }

    /**
     * Boot all bootable services
     *
     */
    public function bootServices(): void
    {
        $bootableServices = $this->findTaggedServiceIds(self::TAG_BOOTABLE);

        foreach ($bootableServices as $serviceId) {
            $service = $this->get($serviceId);

            if ($service instanceof BootableServiceInterface) {
                $service->boot();
            } elseif (method_exists($service, 'register')) {
                // For backward compatibility
                $service->register();
            }
        }
    }

    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]);
    }

    /**
     * Make a new instance of a service
     *
     * @param string $abstract Service ID
     * @param array<string, mixed> $parameters Parameters for the service
     * @return mixed The service instance
     */
    public function make(string $abstract, array $parameters = [])
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \Exception("No binding found for {$abstract}");
        }

        $concrete = $this->bindings[$abstract];

        if ($concrete instanceof \Closure) {
            // Create a new instance even if it's a singleton
            $factory = $this->factories[$abstract];
            return $factory($this, $parameters);
        }

        return $concrete;
    }

    public function extend(string $abstract, \Closure $closure): void
    {
        if (!isset($this->bindings[$abstract])) {
            throw new \Exception("No binding found for {$abstract}");
        }

        $originalFactory = $this->factories[$abstract] ?? function () use ($abstract) {
            return $this->get($abstract);
        };

        $this->bind($abstract, function ($container) use ($closure, $originalFactory) {
            return $closure($originalFactory($container), $container);
        });
    }

    public function factory(string $abstract): \Closure
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }
}
