# StorefrontHooksProvider

<!-- @doc-source: StorefrontHooksProvider -->
Storefront Hooks Provider
Manages Storefront and WooCommerce specific hooks
/

namespace FastyChild\Theme\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Theme\Hooks\ThemeHooks;
use FastyChild\Theme\Hooks\StorefrontHooks;
use FastyChild\Theme\Hooks\WooCommerceHooks;

/**
Theme-specific hooks provider for Storefront

## Methods

### register
<!-- @doc-source: StorefrontHooksProvider.register -->
Storefront Hooks Provider
Manages Storefront and WooCommerce specific hooks
/

namespace FastyChild\Theme\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Theme\Hooks\ThemeHooks;
use FastyChild\Theme\Hooks\StorefrontHooks;
use FastyChild\Theme\Hooks\WooCommerceHooks;

/**
Theme-specific hooks provider for Storefront
/
class StorefrontHooksProvider extends AbstractServiceProvider
{
/**
Register theme-specific hook implementations

#### Returns



### boot
<!-- @doc-source: StorefrontHooksProvider.boot -->
Boot theme hooks - register with hooks manager

#### Returns



### isStorefrontTheme
<!-- @doc-source: StorefrontHooksProvider.isStorefrontTheme -->
Check if parent theme is Storefront

#### Returns



