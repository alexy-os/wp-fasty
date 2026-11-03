<?php

declare(strict_types=1);

/**
 * Deprecated aliases from before the base theme was renamed latte → wp-fasty (2.0). Remove in 3.0.
 */

/** @deprecated 2.0 Use wpfasty_base_theme_dir(). */
function latte_base_theme_dir(): string
{
    _deprecated_function(__FUNCTION__, '2.0', 'wpfasty_base_theme_dir');

    return wpfasty_base_theme_dir();
}

/** @deprecated 2.0 Use wpfasty_base_theme_uri(). */
function latte_base_theme_uri(): string
{
    _deprecated_function(__FUNCTION__, '2.0', 'wpfasty_base_theme_uri');

    return wpfasty_base_theme_uri();
}

/** @deprecated 2.0 Use wpfasty_base_theme_hooks(). */
function latte_base_theme_hooks(): void
{
    _deprecated_function(__FUNCTION__, '2.0', 'wpfasty_base_theme_hooks');
}

/** @deprecated 2.0 Use wpfasty_load_base_theme(). */
function latte_load_base_theme(): void
{
    _deprecated_function(__FUNCTION__, '2.0', 'wpfasty_load_base_theme');
}
