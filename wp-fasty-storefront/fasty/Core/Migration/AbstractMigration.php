<?php
declare(strict_types=1);

namespace FastyChild\Core\Migration;

use FastyChild\Core\Traits\LoggerTrait;
use FastyChild\Core\Utils;

/**
 * Abstract base class for migrations
 */
abstract class AbstractMigration implements MigrationInterface
{
    use LoggerTrait;
    
    /**
     * Migration version
     * @var string
     */
    protected string $version;
    
    /**
     * Migration description
     * @var string
     */
    protected string $description;
    
    /**
     * Constructor
     *
     * @param string $version Migration version
     * @param string $description Migration description
     */
    public function __construct(string $version, string $description = '')
    {
        $this->version = $version;
        $this->description = $description;
    }
    
    /**
     * Get the version this migration applies to
     *
     * @return string Version number
     */
    public function getVersion(): string
    {
        return $this->version;
    }
    
    /**
     * Get migration description
     *
     * @return string Migration description
     */
    public function getDescription(): string
    {
        return $this->description;
    }
    
    /**
     * Check if this migration should be applied
     *
     * @param string $currentVersion Current framework version
     * @return bool True if migration should be applied
     */
    public function shouldApply(string $currentVersion): bool
    {
        // Migration should be applied if current version is lower than migration version
        // but not too low (within major version)
        $currentParts = explode('.', $currentVersion);
        $migrationParts = explode('.', $this->version);
        
        // Only apply if major versions match
        if ($currentParts[0] !== $migrationParts[0]) {
            return false;
        }
        
        return Utils::compareVersions($currentVersion, $this->version) < 0;
    }
    
    /**
     * Apply the migration
     *
     * @return bool True if migration was successful
     */
    public function apply(): bool
    {
        try {
            $this->info(sprintf('Applying migration to version %s: %s', $this->version, $this->description));
            
            // Run the migration
            $result = $this->up();
            
            // Log the result
            if ($result) {
                $this->info(sprintf('Migration to version %s completed successfully', $this->version));
            } else {
                $this->error(sprintf('Migration to version %s failed', $this->version));
            }
            
            return $result;
        } catch (\Throwable $e) {
            $this->error(sprintf(
                'Error applying migration to version %s: %s',
                $this->version,
                $e->getMessage()
            ), ['exception' => $e]);
            
            return false;
        }
    }
    
    /**
     * Rollback the migration
     *
     * @return bool True if rollback was successful
     */
    public function rollback(): bool
    {
        try {
            $this->info(sprintf('Rolling back migration from version %s', $this->version));
            
            // Run the rollback
            $result = $this->down();
            
            // Log the result
            if ($result) {
                $this->info(sprintf('Rollback from version %s completed successfully', $this->version));
            } else {
                $this->error(sprintf('Rollback from version %s failed', $this->version));
            }
            
            return $result;
        } catch (\Throwable $e) {
            $this->error(sprintf(
                'Error rolling back migration from version %s: %s',
                $this->version,
                $e->getMessage()
            ), ['exception' => $e]);
            
            return false;
        }
    }
    
    /**
     * Perform the migration
     *
     * @return bool True if successful
     */
    abstract protected function up(): bool;
    
    /**
     * Reverse the migration
     *
     * @return bool True if successful
     */
    abstract protected function down(): bool;
} 