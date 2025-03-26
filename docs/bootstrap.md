# bootstrap

<!-- @doc-source: bootstrap -->


## Methods

### initializeFramework
<!-- @doc-source: bootstrap.initializeFramework -->
Bootstrap file for the FastyChild framework
Initializes the application and registers necessary providers
/

namespace FastyChild;

use FastyChild\Core\ThemeLoader;
use FastyChild\Core\Exceptions\FastyException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Hooks\Constants;

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
exit;
}

/**
Initialize framework core components

#### Returns



### registerErrorHandler
<!-- @doc-source: bootstrap.registerErrorHandler -->
Register error handler for development

#### Returns



### registerProviders
<!-- @doc-source: bootstrap.registerProviders -->
Register service providers from configuration

#### Parameters

- ``: ThemeLoader $themeLoader
- ``: themeLoader ThemeLoader

#### Returns



### logAndNotifyError
<!-- @doc-source: bootstrap.logAndNotifyError -->
Log and notify about errors

#### Parameters

- ``: null \Throwable $e
- ``: e \Throwable

#### Returns



