<?php
/**
 * Latte adapter for RendererInterface.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Engine;

class LatteRenderer implements RendererInterface
{
    private Engine $latte;

    /** @var list<string> */
    private array $roots;

    /**
     * @param array<int, mixed> $roots    Views roots in lookup order.
     * @param string            $cacheDir Compiled templates directory.
     */
    public function __construct(array $roots, string $cacheDir)
    {
        $this->roots = RootChain::normalize($roots);
        RootChain::ensureDir($cacheDir);

        $this->latte = new Engine();
        $this->latte->setTempDirectory($cacheDir);
        $this->latte->setStrictTypes(true);
        $this->latte->setLoader(new MultiRootLoader($this->roots));
        $this->latte->addExtension(new WordPressExtension());
    }

    public function engine(): string
    {
        return 'latte';
    }

    public function extension(): string
    {
        return '.latte';
    }

    public function exists(string $view): bool
    {
        return RootChain::find($this->roots, $this->withExtension($view)) !== null;
    }

    public function render(string $template, array $context = []): string
    {
        return $this->latte->renderToString($this->withExtension($template), $context);
    }

    public function latte(): Engine
    {
        return $this->latte;
    }

    private function withExtension(string $view): string
    {
        return str_ends_with($view, '.latte') ? $view : $view . '.latte';
    }
}
