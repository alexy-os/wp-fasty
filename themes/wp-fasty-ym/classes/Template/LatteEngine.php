<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Engine;

/**
 * Latte template engine — theme views + optional UI8Kit primitives root.
 */
class LatteEngine implements TemplateEngineInterface
{
    private Engine $latte;
    private string $templateDir;

    /**
     * @param string      $templateDir   Theme views directory
     * @param string      $tempDir       Compiled template cache
     * @param string|null $primitivesDir UI8Kit Latte ui/ directory (optional)
     */
    public function __construct(string $templateDir, string $tempDir, ?string $primitivesDir = null)
    {
        $this->templateDir = $templateDir;

        if (!is_dir($templateDir)) {
            throw new \RuntimeException("Template directory not found: {$templateDir}");
        }

        if (!is_dir($tempDir) && !mkdir($tempDir, 0777, true) && !is_dir($tempDir)) {
            throw new \RuntimeException("Failed to create temp directory: {$tempDir}");
        }

        if (!is_writable($tempDir)) {
            throw new \RuntimeException("Temp directory is not writable: {$tempDir}");
        }

        $roots = [$templateDir];
        if ($primitivesDir !== null && is_dir($primitivesDir)) {
            $roots[] = $primitivesDir;
        }

        $this->latte = new Engine();
        $this->latte->setTempDirectory($tempDir);
        $this->latte->setStrictTypes(true);
        $this->latte->setLoader(new MultiRootLoader($roots));
        $this->latte->addExtension(new WordPressExtension());
    }

    /**
     * {@inheritDoc}
     */
    public function render(string $template, array $context = []): string
    {
        if (!str_ends_with($template, '.latte')) {
            $template .= '.latte';
        }

        if (file_exists($template)) {
            return $this->latte->renderToString($template, $context);
        }

        return $this->latte->renderToString($template, $context);
    }
}
