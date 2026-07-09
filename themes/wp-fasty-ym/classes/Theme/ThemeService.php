<?php

declare(strict_types=1);

namespace WPFasty\Theme;

use WPFasty\Data\ContextFactory;
use WPFasty\Template\TemplateEngineInterface;

/**
 * Theme service for rendering templates
 */
class ThemeService
{
    public function __construct(
        private readonly TemplateEngineInterface $templateEngine,
        private readonly ContextFactory $contextFactory
    ) {
    }

    /**
     * Get context for current page
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        if (is_singular()) {
            return $this->contextFactory->createPageContext();
        }

        if (is_archive() || is_home()) {
            return $this->contextFactory->createArchiveContext();
        }

        return $this->contextFactory->createPageContext();
    }

    /**
     * Render a template with context
     *
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string
    {
        return $this->templateEngine->render($template, $context);
    }
}
