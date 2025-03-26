# ThemeHooks

<!-- @doc-source: ThemeHooks -->
Basic WordPress theme hooks implementation

## Methods

### register
<!-- @doc-source: ThemeHooks.register -->
Basic WordPress theme hooks implementation
/
class ThemeHooks extends AbstractThemeHook
{
/**
Register WordPress theme hooks

#### Returns



### setupTheme
<!-- @doc-source: ThemeHooks.setupTheme -->
Theme setup hook callback

#### Returns



### enqueueScripts
<!-- @doc-source: ThemeHooks.enqueueScripts -->
Enqueue scripts and styles

#### Returns



### registerWidgets
<!-- @doc-source: ThemeHooks.registerWidgets -->
Register sidebar widgets

#### Returns



### filterBodyClasses
<!-- @doc-source: ThemeHooks.filterBodyClasses -->
Filter body classes

#### Parameters

- ``: array $classes Array of body classes
- ``: classes array

#### Returns



### filterExcerptMore
<!-- @doc-source: ThemeHooks.filterExcerptMore -->
Filter excerpt more string

#### Parameters

- ``: string $more The current "more" string
- ``: more string

#### Returns



### filterExcerptLength
<!-- @doc-source: ThemeHooks.filterExcerptLength -->
Filter excerpt length

#### Parameters

- ``: int $length Current excerpt length
- ``: length int

#### Returns



