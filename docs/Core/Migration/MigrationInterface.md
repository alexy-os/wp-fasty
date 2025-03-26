# MigrationInterface

<!-- @doc-source: MigrationInterface -->


## Methods

### getVersion
<!-- @doc-source: MigrationInterface.getVersion -->
Interface for framework migrations
/
interface MigrationInterface
{
/**
Get the version this migration applies to

#### Returns



### shouldApply
<!-- @doc-source: MigrationInterface.shouldApply -->
Check if this migration should be applied

#### Parameters

- ``: string $currentVersion Current framework version
- ``: currentVersion string

#### Returns



### apply
<!-- @doc-source: MigrationInterface.apply -->
Apply the migration

#### Returns



### rollback
<!-- @doc-source: MigrationInterface.rollback -->
Rollback the migration

#### Returns



### getDescription
<!-- @doc-source: MigrationInterface.getDescription -->
Get migration description

#### Returns



