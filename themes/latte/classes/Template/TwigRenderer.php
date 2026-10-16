<?php
/**
 * Twig adapter for RendererInterface (optional — requires twig/twig).
 *
 * Primitives come from ui8kit/codegen Twig output (`generated/ui/<brick>/<Part>.html.twig`)
 * and are registered with UI8Kit\TwigExtension so the DOM matches the Latte primitives.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TwigRenderer implements RendererInterface
{
    private Environment $twig;

    /** @var list<string> */
    private array $roots;

    /**
     * @param array<int, mixed> $roots    Views roots in lookup order.
     * @param string            $cacheDir Compiled templates directory.
     */
    public function __construct(array $roots, string $cacheDir)
    {
        if (!class_exists(Environment::class)) {
            throw new \RuntimeException('TwigRenderer requires twig/twig (composer require twig/twig)');
        }
        $this->roots = RootChain::normalize($roots);
        RootChain::ensureDir($cacheDir);

        $this->twig = new Environment(new FilesystemLoader($this->roots), [
            'cache' => $cacheDir,
            'autoescape' => 'html',
            'strict_variables' => false,
        ]);
        if (class_exists(\UI8Kit\TwigExtension::class)) {
            $this->twig->addExtension(new \UI8Kit\TwigExtension());
        }
        $this->registerCommonSubset();
    }

    public function engine(): string
    {
        return 'twig';
    }

    public function extension(): string
    {
        return '.html.twig';
    }

    public function exists(string $view): bool
    {
        return RootChain::find($this->roots, $this->withExtension($view)) !== null;
    }

    public function render(string $template, array $context = []): string
    {
        return $this->twig->render($this->withExtension($template), $context);
    }

    public function twig(): Environment
    {
        return $this->twig;
    }

    /** Shared filters/functions of the thin-view subset (ADR-001 §2). */
    private function registerCommonSubset(): void
    {
        $this->twig->addFilter(new TwigFilter('esc_url', static fn (mixed $v): string
            => function_exists('esc_url') ? esc_url((string) $v) : htmlspecialchars((string) $v, ENT_QUOTES)));
        $this->twig->addFilter(new TwigFilter('kses', static fn (mixed $v): string
            => function_exists('wp_kses_post') ? wp_kses_post((string) $v) : strip_tags((string) $v), ['is_safe' => ['html']]));
        $this->twig->addFunction(new TwigFunction('icon', static fn (string $name, string $class = 'size-4', string $label = ''): Markup
            => new Markup(Icons::svg($name, $class, $label), 'UTF-8')));
        $this->twig->addFunction(new TwigFunction('t', static fn (string $text, string $domain = 'latte'): string
            => function_exists('__') ? __($text, $domain) : $text)); // phpcs:ignore WordPress.WP.I18n
    }

    private function withExtension(string $view): string
    {
        return str_ends_with($view, '.twig') ? $view : $view . '.html.twig';
    }
}
