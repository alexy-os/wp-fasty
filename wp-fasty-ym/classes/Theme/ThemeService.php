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
        error_log('ThemeService initialized');
    }
    
    /**
     * Get context for current page
     *
     * @return array Context data
     */
    public function context(): array
    {
        error_log('Getting context for page type: ' . $this->getPageType());
        
        if (is_singular()) {
            $contextCollection = $this->contextFactory->createPageContext();
        } elseif (is_archive() || is_home()) {
            $contextCollection = $this->contextFactory->createArchiveContext();
        } else {
            // Default context
            $contextCollection = $this->contextFactory->createPageContext();
        }
        
        $context = $contextCollection->toArray();
        error_log('Context created: ' . json_encode($context));
        
        return $context;
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
        error_log('ThemeService rendering template: ' . $template);
        try {
            $result = $this->templateEngine->render($template, $context);
            error_log('ThemeService render completed');
            return $result;
        } catch (\Throwable $e) {
            error_log('ThemeService render error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get current page type for logging
     */
    private function getPageType(): string
    {
        switch (true) {
            case is_front_page():
                return 'front_page';
            case is_home():
                return 'home';
            case is_single():
                return 'single';
            case is_page():
                return 'page';
            case is_archive():
                return 'archive';
            default:
                return 'unknown';
        }
    }
}
