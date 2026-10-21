<?php
/**
 * Picks the renderer (filter `wpfasty_renderer`) and builds the root chain.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

final class RendererFactory
{
    /**
     * @param string|RendererInterface $engine `latte`, `twig` or a ready renderer.
     * @param array<int, mixed>        $roots  Shared view roots (child → parent).
     * @param array<int, mixed>        $primitives Engine-specific primitives roots, keyed by engine.
     */
    public static function create(
        string|RendererInterface $engine,
        array $roots,
        array $primitives,
        string $cacheDir
    ): RendererInterface {
        if ($engine instanceof RendererInterface) {
            return $engine;
        }
        $engine = strtolower($engine);
        // Twig without twig/twig or its primitives (e.g. the Latte-only parent build): fall back to Latte, so a
        // site whose child asks for Twig keeps rendering — children ship both view sets.
        if ($engine === 'twig' && (!class_exists(\Twig\Environment::class) || empty($primitives['twig']))) {
            $engine = 'latte';
        }
        $chain = array_merge($roots, (array) ($primitives[$engine] ?? []));

        return match ($engine) {
            'twig' => new TwigRenderer($chain, rtrim($cacheDir, '/\\') . '/twig'),
            default => new LatteRenderer($chain, $cacheDir),
        };
    }
}
