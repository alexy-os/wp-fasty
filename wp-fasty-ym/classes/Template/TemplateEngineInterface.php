<?php

declare(strict_types=1);

namespace WPFasty\Template;

/**
 * Interface for template engines
 */
interface TemplateEngineInterface
{
    /**
     * Render a template with context
     *
     * @param string $template The template path/name to render
     * @param array $context The context data to pass to the template
     * @return string The rendered template
     */
    public function render(string $template, array $context = []): string;
} 