<?php

declare(strict_types=1);

/**
 * Container Interface
 *
 * This interface defines the basic contract for a DI container.
 *
 * @package WPFasty\Core
 */

namespace WPFasty\Core;

interface ContainerInterface
{
    /**
     * Tag for bootable services
     */
    public const TAG_BOOTABLE = 'bootable';

    /**
     * Get a service from the container
     *
     * @param string $abstract Name of the service
     * @return mixed The resolved service
     * @throws \Exception When no binding is found.
     */
    public function get(string $abstract);

    /**
     * Get IDs of all registered services
     *
     * @return array<string> Array of service IDs
     */
    public function getServiceIds(): array;

    /**
     * Check if service has a specific tag
     *
     * @param string $serviceId Service ID
     * @param string $tag Tag name
     * @return boolean True if service has the specified tag
     */
    public function hasTag(string $serviceId, string $tag): bool;

    /**
     * Add a tag to a service
     *
     * @param string $serviceId Service ID
     * @param string $tag Tag name
     * @param array<string, mixed> $attributes Tag attributes
     */
    public function addTag(string $serviceId, string $tag, array $attributes = []): void;

    /**
     * Find all services with a specific tag
     *
     * @param string $tag Tag name
     * @return array<string> Array of service IDs
     */
    public function findTaggedServiceIds(string $tag): array;
}
