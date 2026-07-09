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

    /** @return array<string, mixed> */
    public function context(): array
    {
        if (is_front_page() && !is_home()) {
            return $this->contextFactory->createPageContext();
        }
        if (is_home() || is_archive() || is_search()) {
            return $this->contextFactory->createArchiveContext();
        }
        return $this->contextFactory->createPageContext();
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
