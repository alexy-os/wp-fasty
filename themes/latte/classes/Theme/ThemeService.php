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
