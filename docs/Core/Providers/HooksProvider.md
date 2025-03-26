# HooksProvider

<!-- @doc-source: HooksProvider -->
Core Hooks Provider
Base provider for WordPress hooks management
/

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Core\Hooks\HooksManager;

/**
Core hooks provider without theme-specific dependencies

## Methods

### register
<!-- @doc-source: HooksProvider.register -->
Core Hooks Provider
Base provider for WordPress hooks management
/

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Core\Hooks\HooksManager;

/**
Core hooks provider without theme-specific dependencies
/
class HooksProvider extends AbstractServiceProvider
{
/**
Register hooks manager service in the container

#### Returns



### boot
<!-- @doc-source: HooksProvider.boot -->
Boot hooks manager
This only sets up the hooks manager itself, not specific hooks
Specific hooks will be added by theme providers

#### Returns



