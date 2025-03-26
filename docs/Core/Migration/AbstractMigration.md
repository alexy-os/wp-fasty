# AbstractMigration

<!-- @doc-source: AbstractMigration -->
Abstract base class for migrations

## Methods

### __construct
<!-- @doc-source: AbstractMigration.__construct -->
Abstract base class for migrations
/
abstract class AbstractMigration implements MigrationInterface
{
use LoggerTrait;

/**
Migration version

#### Parameters

- ``: version string
- ``: description string

### getVersion
<!-- @doc-source: AbstractMigration.getVersion -->
Get the version this migration applies to

#### Returns



### getDescription
<!-- @doc-source: AbstractMigration.getDescription -->
Get migration description

#### Returns



### shouldApply
<!-- @doc-source: AbstractMigration.shouldApply -->
Check if this migration should be applied

#### Parameters

- ``: string $currentVersion Current framework version
- ``: currentVersion string

#### Returns



### apply
<!-- @doc-source: AbstractMigration.apply -->
Apply the migration

#### Returns



### rollback
<!-- @doc-source: AbstractMigration.rollback -->
Rollback the migration

#### Returns



