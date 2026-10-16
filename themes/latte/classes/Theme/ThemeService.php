<?php

declare(strict_types=1);

namespace WPFasty\Theme;

use WPFasty\Data\ContextFactory;
use WPFasty\Template\TemplateEngineInterface;
use WPFasty\Template\RendererInterface;

/**
 * Context routing + two-pass render (content → layout).
 */
final class ThemeService
{
    public function __construct(
        private readonly TemplateEngineInterface $engine,
        private readonly ContextFactory $contextFactory,
    ) {
    }

    /**
     * Single source for WP template → Latte view mapping.
     */
    public function resolveView(): string
    {
        $view = 'pages/404';

        if (is_404()) {
            $view = 'pages/404';
        } elseif (is_search()) {
            $view = 'pages/search';
        } elseif (is_front_page() && !is_home()) {
            $view = 'pages/front-page';
        } elseif (is_home() || is_archive()) {
            $view = 'pages/archive';
        } elseif (is_attachment()) {
            $view = 'pages/attachment';
        } elseif (is_page()) {
            $view = 'pages/page';
        } elseif (is_singular('post')) {
            $view = 'pages/single';
        } elseif (is_singular()) {
            $view = 'pages/single';
        }

        /** @var mixed $filtered */
        $filtered = apply_filters('wpfasty_resolve_view', $view);
        return is_string($filtered) && $filtered !== '' ? $filtered : $view;
    }

    /** @return array<string, mixed> */
    public function context(?string $view = null): array
    {
        $view ??= $this->resolveView();

        if ($view === 'pages/404') {
            return $this->contextFactory->createErrorContext();
        }
        // Context follows the WP query, not the view name, so child themes can
        // map custom views (e.g. pages/shop) via `wpfasty_resolve_view`.
        if (in_array($view, ['pages/search', 'pages/archive'], true) || is_search() || is_archive() || is_home()) {
            return $this->contextFactory->createArchiveContext();
        }
        return $this->contextFactory->createPageContext();
    }

    public function renderer(): TemplateEngineInterface
    {
        return $this->engine;
    }

    public function engine(): string
    {
        return $this->engine instanceof RendererInterface ? $this->engine->engine() : 'latte';
    }

    /** @param array<string, mixed> $context */
    public function render(string $template, array $context = []): string
    {
        return $this->engine->render($template, $context);
    }

    /**
     * Render page view into layout shell.
     *
     * @param array<string, mixed> $context
     */
    public function renderPage(string $view, array $context): string
    {
        $context['content'] = $this->render($view, $context);
        return $this->render('layout/default', $context);
    }
}
