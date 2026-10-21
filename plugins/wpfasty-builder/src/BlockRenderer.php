<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Source\SourceInterface;

/**
 * Renders one block template of a source with its props ($p), in the engine of the template name:
 * blocks/<name>.latte on a Latte engine of its own (the codegen-x ui_* functions are registered there), and
 * blocks/<name>.html.twig on a Twig environment over the source's generated Twig package (same specs, same DOM).
 * Each engine is created only when a template of that engine is rendered, so a Twig site needs no Latte library
 * (and a Latte site no Twig). The PHP runtimes always come from the bundled ui8kit source; templates of remote
 * sources render sandboxed: Latte through TemplateGuard, Twig through Twig's SandboxExtension with the same
 * allowlist idea (the tags, filters and functions the generated templates use).
 */
final class BlockRenderer
{
    public const TWIG_EXT = '.html.twig';

    /** @var array<string, object> */
    private array $engines = [];

    /** @var array<string, object|null> */
    private array $twigs = [];

    /** @param array<string, SourceInterface> $sources */
    public function __construct(private readonly array $sources, private readonly ?string $cacheDir)
    {
    }

    /** loads the Latte runtime of the bundled source (needs latte/latte) */
    public static function loadRuntime(): void
    {
        if (class_exists('\UI8Kit\Latte\Extension', false) || !class_exists('\Latte\Engine')) {
            return;
        }
        $php = dirname(__DIR__) . '/sources/ui8kit/latte/php';
        foreach (['CnMerge', 'Runtime', 'Extension'] as $f) {
            if (is_file("$php/$f.php")) {
                require_once "$php/$f.php";
            }
        }
    }

    /** loads the Twig runtime of the bundled source (needs twig/twig) */
    public static function loadTwigRuntime(): bool
    {
        if (class_exists('\UI8Kit\Twig\Extension', false)) {
            return true;
        }
        if (!class_exists('\Twig\Environment')) {
            return false;
        }
        $php = dirname(__DIR__) . '/sources/ui8kit/twig/php';
        foreach (['CnMerge', 'Runtime', 'Extension'] as $f) {
            if (!is_file("$php/$f.php")) {
                return false;
            }
            require_once "$php/$f.php";
        }
        return true;
    }

    /** blocks/<name>.latte → blocks/<name>.html.twig */
    public static function twigName(string $template): string
    {
        return str_ends_with($template, '.latte') ? substr($template, 0, -6) . self::TWIG_EXT : $template;
    }

    /** @param array<string, mixed> $props */
    public function render(string $source, string $template, array $props): string
    {
        $twig = str_ends_with($template, self::TWIG_EXT);
        $latteName = $twig ? substr($template, 0, -strlen(self::TWIG_EXT)) . '.latte' : $template;
        if (preg_match(Catalog::TEMPLATE, $latteName) !== 1) {
            return '';
        }
        try {
            if ($twig) {
                $env = $this->twig($source);
                if ($env !== null) {
                    return $env->render($template, ['p' => $props]);
                }
                // A source without a Twig package: its Latte template, when Latte is available.
            }
            $engine = $this->engine($source);
            return $engine !== null ? $engine->renderToString($latteName, ['p' => $props]) : '';
        } catch (\Throwable $e) {
            $msg = function_exists('current_user_can') && current_user_can('edit_pages') ? $e->getMessage() : '';
            return '<!-- wpfasty-builder: ' . htmlspecialchars($template . ' ' . $msg, ENT_QUOTES) . ' -->';
        }
    }

    /** the Latte engine of a source, null without Latte or without templates */
    public function engine(string $source): ?\Latte\Engine
    {
        if (isset($this->engines[$source])) {
            return $this->engines[$source];
        }
        $src = $this->sources[$source] ?? null;
        if ($src === null || !is_dir($src->templateRoot() . '/blocks')) {
            return null;
        }
        self::loadRuntime();
        if (!class_exists('\UI8Kit\Latte\Extension')) {
            return null;
        }
        $tmp = $this->cache($source);
        $engine = \UI8Kit\Latte\Extension::engine($src->templateRoot(), $tmp);
        if ($src->sandboxed()) {
            $engine->addExtension(self::guard($engine));
        }
        return $this->engines[$source] = $engine;
    }

    /** the Twig environment of a source's generated Twig package, null without twig/twig or without the package */
    public function twig(string $source): ?\Twig\Environment
    {
        if (array_key_exists($source, $this->twigs)) {
            return $this->twigs[$source];
        }
        $src = $this->sources[$source] ?? null;
        $root = $src?->twigRoot();
        if ($src === null || $root === null || !self::loadTwigRuntime()) {
            return $this->twigs[$source] = null;
        }
        $tmp = $this->cache($source . '/twig');
        $env = \UI8Kit\Twig\Extension::environment($root, ['cache' => $tmp ?? false, 'auto_reload' => true]);
        if ($src->sandboxed()) {
            $env->addExtension(self::sandbox($env));
        }
        return $this->twigs[$source] = $env;
    }

    public static function guard(\Latte\Engine $engine): TemplateGuard
    {
        $fns = [];
        foreach (array_keys($engine->getFunctions()) as $name) {
            if (str_starts_with((string) $name, 'ui_')) {
                $fns[] = (string) $name;
            }
        }
        return new TemplateGuard($fns);
    }

    /** Twig sandbox (global): only the tags, filters and functions of the generated templates; no methods/properties */
    public static function sandbox(\Twig\Environment $env): \Twig\Extension\SandboxExtension
    {
        $fns = ['include'];
        foreach ($env->getExtension(\UI8Kit\Twig\Extension::class)->getFunctions() as $f) {
            $fns[] = $f->getName();
        }
        $policy = new \Twig\Sandbox\SecurityPolicy(
            ['if', 'for', 'set'],
            ['escape', 'e', 'raw', 'merge'],
            [],
            [],
            $fns,
        );
        return new \Twig\Extension\SandboxExtension($policy, true);
    }

    private function cache(string $sub): ?string
    {
        if ($this->cacheDir === null) {
            return null;
        }
        $tmp = $this->cacheDir . '/' . $sub;
        if (!is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }
        return is_dir($tmp) ? $tmp : null;
    }
}
