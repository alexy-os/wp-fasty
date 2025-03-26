# ThemeHooks

<!-- @doc-source: ThemeHooks -->


## Methods

### canRegister
<!-- @doc-source: ThemeHooks.canRegister -->
Theme Hooks
Handles basic WordPress hook overrides
/

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class ThemeHooks extends AbstractHooks {
/**
Check if this hook can be registered

#### Returns



### register
<!-- @doc-source: ThemeHooks.register -->
Register hook handlers

#### Returns



### addBodyClasses
<!-- @doc-source: ThemeHooks.addBodyClasses -->
Add custom body classes

#### Parameters

- ``: array $classes Existing body classes
- ``: classes array

#### Returns



### modifyExcerptMore
<!-- @doc-source: ThemeHooks.modifyExcerptMore -->
Modify excerpt "read more" string

#### Parameters

- ``: string $more Default "read more" string
- ``: more string

#### Returns



### modifyExcerptLength
<!-- @doc-source: ThemeHooks.modifyExcerptLength -->
Modify excerpt length

#### Parameters

- ``: int $length Default excerpt length
- ``: length int

#### Returns



### disableEmoji
<!-- @doc-source: ThemeHooks.disableEmoji -->
Disable WordPress emoji functionality

#### Returns



### cleanHead
<!-- @doc-source: ThemeHooks.cleanHead -->
Clean up WordPress head

#### Returns



