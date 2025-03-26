# AssetsService

<!-- @doc-source: AssetsService -->


## Methods

### __construct
<!-- @doc-source: AssetsService.__construct -->
Assets Service
Handles theme assets loading (styles and scripts)
/

namespace FastyChild\Services;

use FastyChild\Core\Application;

class AssetsService {
/**
Application instance

#### Parameters

- ``: app Application

### enqueueStyles
<!-- @doc-source: AssetsService.enqueueStyles -->
Enqueue theme styles

#### Returns



### enqueueScripts
<!-- @doc-source: AssetsService.enqueueScripts -->
Enqueue theme scripts

#### Returns



### enqueueAdminStyles
<!-- @doc-source: AssetsService.enqueueAdminStyles -->
Enqueue admin styles

#### Returns



### enqueueAdminScripts
<!-- @doc-source: AssetsService.enqueueAdminScripts -->
Enqueue admin scripts

#### Returns



### getFileVersion
<!-- @doc-source: AssetsService.getFileVersion -->
Get file version based on file modification time

#### Parameters

- ``: string $file Path to file
- ``: file mixed

#### Returns



