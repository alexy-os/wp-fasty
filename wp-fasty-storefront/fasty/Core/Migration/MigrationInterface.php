<?php
declare(strict_types=1);

namespace FastyChild\Core\Migration;

/**
 * Interface for framework migrations
 */
interface MigrationInterface
{
    /**
     * Get the version this migration applies to
     *
     * @return string Version number (e.g. '1.0.0')
     */
    public function getVersion(): string;
    
    /**
     * Check if this migration should be applied
     *
     * @param string $currentVersion Current framework version
     * @return bool True if migration should be applied
     */
    public function shouldApply(string $currentVersion): bool;
    
    /**
     * Apply the migration
     *
     * @return bool True if migration was successful
     */
    public function apply(): bool;
    
    /**
     * Rollback the migration
     *
     * @return bool True if rollback was successful
     */
    public function rollback(): bool;
    
    /**
     * Get migration description
     *
     * @return string Migration description
     */
    public function getDescription(): string;
} 