<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Engine;

/**
 * Latte engine — multi-root loader (child views → parent views → UI8Kit).
 */
final class LatteEngine implements TemplateEngineInterface
{
    private Engine $latte;

    /**
     * @param array<int, string> $roots Absolute directories; first match wins
     */
    public function __construct(array $roots, string $cacheDir)
    {
        $roots = array_values(array_filter(
            $roots,
            static fn (mixed $dir): bool => is_string($dir) && $dir !== '' && is_dir($dir)
        ));

        if ($roots === []) {
            throw new \RuntimeException('LatteEngine requires at least one views root');
        }

        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
            throw new \RuntimeException('Cannot create cache directory: ' . $cacheDir);
        }

        $this->latte = new Engine();
        $this->latte->setTempDirectory($cacheDir);
        $this->latte->setStrictTypes(true);
        $this->latte->setLoader(new MultiRootLoader($roots));
        $this->latte->addExtension(new WordPressExtension());
    }

    /**
     * @param array<string, mixed> $context
     */
    public function render(string $template, array $context = []): string
    {
        if (!str_ends_with($template, '.latte')) {
            $template .= '.latte';
        }
        return $this->latte->renderToString($template, $context);
    }
}

