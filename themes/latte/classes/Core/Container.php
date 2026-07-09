<?php

declare(strict_types=1);

namespace WPFasty\Core;

/**
 * Minimal DI container — bind / singleton / bootable tags.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, mixed> */
    private array $bindings = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    /** @var array<string, callable> */
    private array $factories = [];

    /** @var array<string, array<string, array<string, mixed>>> */
    private array $tags = [];

    public function bind(string $id, mixed $concrete): void
    {
        $this->bindings[$id] = $concrete;
        if ($concrete instanceof \Closure) {
            $this->factories[$id] = $concrete;
        }
    }

    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        $this->bindings[$id] = function () use ($id, $factory) {
            if (!isset($this->instances[$id])) {
                $this->instances[$id] = $factory($this);
            }
            return $this->instances[$id];
        };
    }

    public function get(string $id): mixed
    {
        if (!isset($this->bindings[$id])) {
            throw new \RuntimeException("No binding for \"{$id}\"");
        }

        $concrete = $this->bindings[$id];
        return $concrete instanceof \Closure ? $concrete($this) : $concrete;
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id]);
    }

    public function addTag(string $id, string $tag, array $attributes = []): void
    {
        $this->tags[$id][$tag] = $attributes;
    }

    public function findTaggedServiceIds(string $tag): array
    {
        $ids = [];
        foreach ($this->tags as $id => $tags) {
            if (isset($tags[$tag])) {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    public function bootServices(): void
    {
        foreach ($this->findTaggedServiceIds(self::TAG_BOOTABLE) as $id) {
            $service = $this->get($id);
            if (!$service instanceof BootableServiceInterface) {
                throw new \RuntimeException(
                    "Bootable service \"{$id}\" must implement BootableServiceInterface"
                );
            }
            $service->boot();
        }
    }
}
