<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Engine;

/**
 * Latte engine — theme views + UI8Kit primitives root.
 */
final class LatteEngine implements TemplateEngineInterface
{
    private Engine $latte;

    public function __construct(string $viewsDir, string $cacheDir, ?string $primitivesDir = null)
    {
        if (!is_dir($viewsDir)) {
            throw new \RuntimeException("Views directory not found: {$viewsDir}");
        }
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
            throw new \RuntimeException("Cannot create cache: {$cacheDir}");
        }

        $roots = [$viewsDir];
        if ($primitivesDir !== null && is_dir($primitivesDir)) {
            $roots[] = $primitivesDir;
        }

        $this->latte = new Engine();
        $this->latte->setTempDirectory($cacheDir);
        $this->latte->setStrictTypes(true);
        $this->latte->setLoader(new MultiRootLoader($roots));
        $this->latte->addExtension(new WordPressExtension());
    }

    public function render(string $template, array $context = []): string
    {
        if (!str_ends_with($template, '.latte')) {
            $template .= '.latte';
        }
        return $this->latte->renderToString($template, $context);
    }
}
