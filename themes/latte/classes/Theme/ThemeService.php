<?php

declare(strict_types=1);

namespace WPFasty\Theme;

use WPFasty\Data\ContextFactory;
use WPFasty\Template\TemplateEngineInterface;

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
        if (is_404()) {
            return 'pages/404';
        }
        if (is_search()) {
            return 'pages/search';
        }
        if (is_front_page() && !is_home()) {
            return 'pages/front-page';
        }
        if (is_home() || is_archive()) {
            return 'pages/archive';
        }
        if (is_singular('post')) {
            return 'pages/single';
        }
        if (is_page()) {
            return 'pages/page';
        }

        return 'pages/404';
    }

    /** @return array<string, mixed> */
    public function context(?string $view = null): array
    {
        $view ??= $this->resolveView();

        return match ($view) {
            'pages/404' => $this->contextFactory->createErrorContext(),
            'pages/search', 'pages/archive' => $this->contextFactory->createArchiveContext(),
            default => $this->contextFactory->createPageContext(),
        };
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
