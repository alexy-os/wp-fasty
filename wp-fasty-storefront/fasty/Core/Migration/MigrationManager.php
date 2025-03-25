<?php
declare(strict_types=1);

namespace FastyChild\Core\Migration;

use FastyChild\Core\Traits\LoggerTrait;
use FastyChild\Core\Utils;
use FastyChild\Hooks\Constants;

/**
 * Manages framework migrations
 */
class MigrationManager
{
    use LoggerTrait;
    
    /**
     * Option name for storing current framework version
     */
    private const VERSION_OPTION = 'fasty_framework_version';
    
    /**
     * Option name for storing applied migrations
     */
    private const MIGRATIONS_OPTION = 'fasty_applied_migrations';
    
    /**
     * Available migrations
     * @var array<string, MigrationInterface>
     */
    private array $migrations = [];
    
    /**
     * Applied migrations
     * @var array<string>
     */
    private array $appliedMigrations = [];
    
    /**
     * Current framework version
     * @var string
     */
    private string $currentVersion;
    
    /**
     * Target framework version
     * @var string
     */
    private string $targetVersion;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->loadMigrationState();
        $this->targetVersion = Constants::VERSION;
    }
    
    /**
     * Load current migration state
     *
     * @return void
     */
    private function loadMigrationState(): void
    {
        // Get current version from database or use default
        $this->currentVersion = get_option(self::VERSION_OPTION, '0.0.0');
        
        // Get applied migrations from database
        $migrations = get_option(self::MIGRATIONS_OPTION, []);
        if (is_array($migrations)) {
            $this->appliedMigrations = $migrations;
        }
    }
    
    /**
     * Save current migration state
     *
     * @return void
     */
    private function saveMigrationState(): void
    {
        update_option(self::VERSION_OPTION, $this->currentVersion);
        update_option(self::MIGRATIONS_OPTION, $this->appliedMigrations);
    }
    
    /**
     * Register a migration
     *
     * @param MigrationInterface $migration Migration to register
     * @return self
     */
    public function addMigration(MigrationInterface $migration): self
    {
        $version = $migration->getVersion();
        $this->migrations[$version] = $migration;
        
        return $this;
    }
    
    /**
     * Get all available migrations
     *
     * @return array<string, MigrationInterface> All registered migrations
     */
    public function getAllMigrations(): array
    {
        return $this->migrations;
    }
    
    /**
     * Get migrations that should be applied
     *
     * @return array<string, MigrationInterface> Migrations that should be applied
     */
    public function getPendingMigrations(): array
    {
        $pending = [];
        
        foreach ($this->migrations as $version => $migration) {
            // Skip already applied migrations
            if (in_array($version, $this->appliedMigrations, true)) {
                continue;
            }
            
            // Check if migration should be applied
            if ($migration->shouldApply($this->currentVersion)) {
                $pending[$version] = $migration;
            }
        }
        
        // Sort by version
        uksort($pending, [Utils::class, 'compareVersions']);
        
        return $pending;
    }
    
    /**
     * Run all pending migrations
     *
     * @return bool True if all migrations were applied successfully
     */
    public function migrate(): bool
    {
        // Get pending migrations
        $pendingMigrations = $this->getPendingMigrations();
        
        if (empty($pendingMigrations)) {
            $this->info('No pending migrations found.');
            return true;
        }
        
        $this->info(sprintf(
            'Found %d pending migrations. Running migrations from %s to %s...',
            count($pendingMigrations),
            $this->currentVersion,
            $this->targetVersion
        ));
        
        // Apply each migration
        $success = true;
        foreach ($pendingMigrations as $version => $migration) {
            // Run migration with WordPress hook
            $result = false;
            
            // Allow monitoring or modifying migration via hook
            do_action(Constants::HOOK_FASTY_MIGRATION, $migration, 'before');
            
            try {
                $result = $migration->apply();
            } catch (\Throwable $e) {
                $this->error('Migration failed with exception: ' . $e->getMessage(), [
                    'version' => $version,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $result = false;
            }
            
            do_action(Constants::HOOK_FASTY_MIGRATION, $migration, 'after', $result);
            
            // Process result
            if ($result) {
                // Mark migration as applied
                $this->appliedMigrations[] = $version;
                $this->currentVersion = $version;
                $this->saveMigrationState();
            } else {
                $success = false;
                $this->error('Migration failed. Stopping migration process.', [
                    'version' => $version
                ]);
                break;
            }
        }
        
        // Save final state
        $this->currentVersion = $this->targetVersion;
        $this->saveMigrationState();
        
        return $success;
    }
    
    /**
     * Rollback to a specific version
     *
     * @param string $targetVersion Target version to rollback to
     * @return bool True if rollback was successful
     */
    public function rollback(string $targetVersion): bool
    {
        // Cannot rollback to higher version
        if (Utils::compareVersions($targetVersion, $this->currentVersion) > 0) {
            $this->error('Cannot rollback to a higher version.', [
                'current' => $this->currentVersion,
                'target' => $targetVersion
            ]);
            return false;
        }
        
        // Get migrations to rollback (in reverse order)
        $migrationsToRollback = array_filter(
            array_reverse($this->appliedMigrations),
            function ($version) use ($targetVersion) {
                return Utils::compareVersions($version, $targetVersion) > 0;
            }
        );
        
        if (empty($migrationsToRollback)) {
            $this->info('No migrations to rollback.');
            return true;
        }
        
        $this->info(sprintf(
            'Rolling back %d migrations from version %s to %s...',
            count($migrationsToRollback),
            $this->currentVersion,
            $targetVersion
        ));
        
        // Rollback each migration
        $success = true;
        foreach ($migrationsToRollback as $version) {
            if (!isset($this->migrations[$version])) {
                $this->error('Migration not found for rollback.', [
                    'version' => $version
                ]);
                $success = false;
                break;
            }
            
            $migration = $this->migrations[$version];
            
            // Run rollback with WordPress hook
            do_action(Constants::HOOK_FASTY_MIGRATION, $migration, 'before_rollback');
            
            $result = false;
            try {
                $result = $migration->rollback();
            } catch (\Throwable $e) {
                $this->error('Rollback failed with exception: ' . $e->getMessage(), [
                    'version' => $version,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $result = false;
            }
            
            do_action(Constants::HOOK_FASTY_MIGRATION, $migration, 'after_rollback', $result);
            
            // Process result
            if ($result) {
                // Remove migration from applied list
                $this->appliedMigrations = array_diff($this->appliedMigrations, [$version]);
                $this->currentVersion = $targetVersion;
                $this->saveMigrationState();
            } else {
                $success = false;
                $this->error('Rollback failed. Stopping rollback process.', [
                    'version' => $version
                ]);
                break;
            }
        }
        
        return $success;
    }
    
    /**
     * Get current framework version
     *
     * @return string Current version
     */
    public function getCurrentVersion(): string
    {
        return $this->currentVersion;
    }
    
    /**
     * Get target framework version
     *
     * @return string Target version
     */
    public function getTargetVersion(): string
    {
        return $this->targetVersion;
    }
} 