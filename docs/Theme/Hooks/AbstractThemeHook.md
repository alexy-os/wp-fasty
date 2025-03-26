# AbstractThemeHook

<!-- @doc-source: AbstractThemeHook -->
Abstract base class for theme-specific hooks

## Methods

### isTheme
<!-- @doc-source: AbstractThemeHook.isTheme -->
Abstract base class for theme-specific hooks
/
abstract class AbstractThemeHook extends AbstractHook
{
/**
Check if current theme is the given theme or child of it

#### Parameters

- ``: string $themeSlug Theme slug/text domain to check
- ``: themeSlug string

#### Returns



### isWooCommerceActive
<!-- @doc-source: AbstractThemeHook.isWooCommerceActive -->
Check if WooCommerce is active

#### Returns



### getThemeOption
<!-- @doc-source: AbstractThemeHook.getThemeOption -->
Get theme option with fallback

#### Parameters

- ``: string $option Option name
- ``: mixed $default Default value
- ``: option string
- ``: default mixed

#### Returns



### getTemplatePart
<!-- @doc-source: AbstractThemeHook.getTemplatePart -->
Load template part from theme

#### Parameters

- ``: string $slug Template slug
- ``: string $name Template name
- ``: array $args Additional arguments
- ``: slug string
- ``: name string
- ``: args array

#### Returns



