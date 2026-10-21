<?php
/**
 * Picks the renderer (filter `wpfasty_renderer`, default Engines::DEFAULT) and builds the root chain; an engine the
 * build lacks throws EngineUnavailable.
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
        // Isolated runtimes (ADR-001, K7): an engine this build lacks (library or primitives) is an error, never
        // rendered by the other engine.
        if (!Engines::available($engine) || empty($primitives[$engine])) {
            throw EngineUnavailable::for($engine);
        }
        $chain = array_merge($roots, (array) $primitives[$engine]);

        return $engine === 'twig'
            ? new TwigRenderer($chain, rtrim($cacheDir, '/\\') . '/twig')
            : new LatteRenderer($chain, $cacheDir);
    }
}
