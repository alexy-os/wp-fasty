# WooCommerceHooks

<!-- @doc-source: WooCommerceHooks -->


## Methods

### canRegister
<!-- @doc-source: WooCommerceHooks.canRegister -->
WooCommerce Hooks
Handles WooCommerce-specific customizations
/

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class WooCommerceHooks extends AbstractHooks {
/**
Check if WooCommerce is active

#### Returns



### register
<!-- @doc-source: WooCommerceHooks.register -->
Register hook handlers

#### Returns



### modifyProductTitleClasses
<!-- @doc-source: WooCommerceHooks.modifyProductTitleClasses -->
Modify product title classes

#### Parameters

- ``: string $classes Default classes
- ``: classes string

#### Returns



### modifySaleFlash
<!-- @doc-source: WooCommerceHooks.modifySaleFlash -->
Modify sale flash

#### Parameters

- ``: string $html Sale flash HTML
- ``: null \WP_Post $post Post object
- ``: null \WC_Product $product Product object
- ``: html string
- ``: post mixed
- ``: product mixed

#### Returns



### modifyRelatedProductsArgs
<!-- @doc-source: WooCommerceHooks.modifyRelatedProductsArgs -->
Modify related products args

#### Parameters

- ``: array $args Default args
- ``: args array

#### Returns



### modifyCheckoutFields
<!-- @doc-source: WooCommerceHooks.modifyCheckoutFields -->
Modify checkout fields

#### Parameters

- ``: array $fields Default fields
- ``: fields array

#### Returns



### overrideWooCommerceTemplates
<!-- @doc-source: WooCommerceHooks.overrideWooCommerceTemplates -->
Override WooCommerce templates

#### Parameters

- ``: string $template Template path
- ``: string $template_name Template name
- ``: string $template_path Template directory
- ``: template string
- ``: template_name string
- ``: template_path string

#### Returns



### modifyProductTabs
<!-- @doc-source: WooCommerceHooks.modifyProductTabs -->
Modify product tabs

#### Parameters

- ``: array $tabs Default tabs
- ``: tabs array

#### Returns



### overrideWooCommerceHooks
<!-- @doc-source: WooCommerceHooks.overrideWooCommerceHooks -->
Override WooCommerce hooks

#### Returns



