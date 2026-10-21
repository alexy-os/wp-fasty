<?php
/**
 * The template engines of this parent build (ADR-001, K7: isolated runtimes).
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

/**
 * Release builds ship ONE engine: `latte.zip` renders Latte only, `twig.zip` Twig only (theme:pack rewrites BUILT and
 * DEFAULT). The repo parent ships both for development and the parity checks. An engine this build lacks is never
 * replaced by the other one: RendererFactory throws EngineUnavailable and wp-admin shows why.
 */
final class Engines
{
    /** engines of this build */
    public const BUILT = ['latte', 'twig'];

    /** the renderer of this build when nothing asks for another (filter `wpfasty_renderer`) */
    public const DEFAULT = 'latte';

    private const RUNTIME = ['latte' => '\\Latte\\Engine', 'twig' => '\\Twig\\Environment'];

    /** true when this build ships $engine and its library is installed */
    public static function available(string $engine): bool
    {
        return in_array($engine, self::BUILT, true) && class_exists(self::RUNTIME[$engine] ?? '\\stdClass\\None');
    }

    /** the engine asked for by the site (child theme / filter), default of this build otherwise */
    public static function requested(): mixed
    {
        return function_exists('apply_filters') ? apply_filters('wpfasty_renderer', self::DEFAULT) : self::DEFAULT;
    }
}
