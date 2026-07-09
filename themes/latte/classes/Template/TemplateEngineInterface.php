<?php

declare(strict_types=1);

namespace WPFasty\Template;

interface TemplateEngineInterface
{
    /**
     * @param string               $template Template logical name
     * @param array<string, mixed> $context  Template variables
     */
    public function render(string $template, array $context = []): string;
}
