<?php

declare(strict_types=1);

namespace WPFasty\Theme;

use WPFasty\Core\ContainerInterface;
use WPFasty\Data\ContextFactory;
use WPFasty\Template\TemplateEngineInterface;

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
     * @return array Context data
     */
    public function context(): array
    {
        if (is_singular()) {
            $contextCollection = $this->contextFactory->createPageContext();
        } elseif (is_archive() || is_home()) {
            $contextCollection = $this->contextFactory->createArchiveContext();
        } else {
            // Default context
            $contextCollection = $this->contextFactory->createPageContext();
        }
        
        return $contextCollection->toArray();
    }
    
    /**
     * Render a template with context
     *
     * @param string $template Template name
     * @param array $context Context data
     * @return string Rendered template
     */
    public function render(string $template, array $context = []): string
    {
        return $this->templateEngine->render($template, $context);
    }
}
