<?php

declare(strict_types=1);

namespace WPFasty\Template;

interface TemplateEngineInterface
{
    /** @param array<string, mixed> $context */
    public function render(string $template, array $context = []): string;
}
