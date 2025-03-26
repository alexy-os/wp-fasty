# MigrationManager

<!-- @doc-source: MigrationManager -->
Manages framework migrations

## Methods

### __construct
<!-- @doc-source: MigrationManager.__construct -->
Manages framework migrations
/
class MigrationManager
{
use LoggerTrait;

/**
Option name for storing current framework version
/
private const VERSION_OPTION = 'fasty_framework_version';

/**
Option name for storing applied migrations
/
private const MIGRATIONS_OPTION = 'fasty_applied_migrations';

/**
Available migrations

### loadMigrationState
<!-- @doc-source: MigrationManager.loadMigrationState -->
Load current migration state

#### Returns



### saveMigrationState
<!-- @doc-source: MigrationManager.saveMigrationState -->
Save current migration state

#### Returns



### addMigration
<!-- @doc-source: MigrationManager.addMigration -->
Register a migration

#### Parameters

- ``: MigrationInterface $migration Migration to register
- ``: migration MigrationInterface

#### Returns



### getAllMigrations
<!-- @doc-source: MigrationManager.getAllMigrations -->
Get all available migrations

#### Returns



### getPendingMigrations
<!-- @doc-source: MigrationManager.getPendingMigrations -->
Get migrations that should be applied

#### Returns



### migrate
<!-- @doc-source: MigrationManager.migrate -->
Run all pending migrations

#### Returns



### rollback
<!-- @doc-source: MigrationManager.rollback -->
Rollback to a specific version

#### Parameters

- ``: string $targetVersion Target version to rollback to
- ``: targetVersion string

#### Returns



### getCurrentVersion
<!-- @doc-source: MigrationManager.getCurrentVersion -->
Get current framework version

#### Returns



### getTargetVersion
<!-- @doc-source: MigrationManager.getTargetVersion -->
Get target framework version

#### Returns



