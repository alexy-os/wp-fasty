<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Source\SourceInterface;

/**
 * Renders one block template of a source with its props ($p), in the runtime of the template name:
 * blocks/<name>.latte on a Latte engine of its own (the codegen-x ui_* functions are registered there), and
 * blocks/<name>.html.twig on a Twig environment over the source's Twig package. A source serves ONE runtime
 * (SourceInterface::runtime()); a template of the other runtime, or one missing from the source, renders nothing and
 * is reported (action `wpfasty_builder_missing_block`) — never through the other engine (ADR-001, K7). Each engine is
 * created only when a template of its runtime renders. The PHP runtimes come from the plugin build of that runtime;
 * templates of remote sources render sandboxed: Latte through TemplateGuard, Twig through Twig's SandboxExtension.
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
        // UI8Kit 3 runtime of the plugin first: its CnMerge is the class merge of the generated runtime too
        if (!Primitives::loadRuntime('latte')) {
            return;
        }
        $php = dirname(__DIR__) . '/sources/ui8kit/latte/php';
        foreach (['Runtime', 'Extension'] as $f) {
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
        if (!Primitives::loadRuntime('twig')) {
            return false;
        }
        $php = dirname(__DIR__) . '/sources/ui8kit/twig/php';
        foreach (['Runtime', 'Extension'] as $f) {
            if (!is_file("$php/$f.php")) {
                return false;
            }
            require_once "$php/$f.php";
        }
        return true;
    }

    /** @param array<string, mixed> $props */
    public function render(string $source, string $template, array $props): string
    {
        if (preg_match(Catalog::TEMPLATE, $template) !== 1) {
            return '';
        }
        $runtime = str_ends_with($template, self::TWIG_EXT) ? 'twig' : 'latte';
        $src = $this->sources[$source] ?? null;
        if ($src === null || $src->runtime() !== $runtime || !is_file($src->templateRoot() . '/' . $template)) {
            self::missing($source, $template);
            return '';
        }
        try {
            $engine = $runtime === 'twig' ? $this->twig($source) : $this->engine($source);
            if ($engine === null) {
                self::missing($source, $template);
                return '';
            }
            return $engine instanceof \Twig\Environment
                ? $engine->render($template, ['p' => $props])
                : $engine->renderToString($template, ['p' => $props]);
        } catch (\Throwable $e) {
            $msg = function_exists('current_user_can') && current_user_can('edit_pages') ? $e->getMessage() : '';
            return '<!-- wpfasty-builder: ' . htmlspecialchars($template . ' ' . $msg, ENT_QUOTES) . ' -->';
        }
    }

    private static function missing(string $source, string $template): void
    {
        if (function_exists('do_action')) {
            do_action('wpfasty_builder_missing_block', $source, $template);
        }
    }

    /** the Latte engine of a Latte source, null without latte/latte, the plugin's Latte runtime or a Latte source */
    public function engine(string $source): ?\Latte\Engine
    {
        if (isset($this->engines[$source])) {
            return $this->engines[$source];
        }
        $src = $this->sources[$source] ?? null;
        if ($src === null || $src->runtime() !== 'latte' || !is_dir($src->templateRoot() . '/blocks')) {
            return null;
        }
        self::loadRuntime();
        if (!class_exists('\UI8Kit\Latte\Extension')) {
            return null;
        }
        $tmp = $this->cache($source);
        $engine = \UI8Kit\Latte\Extension::engine($src->templateRoot(), $tmp);
        // bricks are adapters over the plugin's UI8Kit 3 primitives (rendered on a trusted engine, see Primitives)
        $engine->addFunction('ui_prim', static fn (string $name, array $args = []): \Latte\Runtime\Html
            => new \Latte\Runtime\Html(Primitives::latte($name, $args)));
        if ($src->sandboxed()) {
            $engine->addExtension(self::guard($engine));
        }
        return $this->engines[$source] = $engine;
    }

    /** the Twig environment of a Twig source, null without twig/twig, the plugin's Twig runtime or a Twig source */
    public function twig(string $source): ?\Twig\Environment
    {
        if (array_key_exists($source, $this->twigs)) {
            return $this->twigs[$source];
        }
        $src = $this->sources[$source] ?? null;
        $root = $src?->templateRoot();
        if ($src === null || $src->runtime() !== 'twig' || !is_dir($root . '/blocks') || !self::loadTwigRuntime()) {
            return $this->twigs[$source] = null;
        }
        $tmp = $this->cache($source . '/twig');
        $env = \UI8Kit\Twig\Extension::environment($root, ['cache' => $tmp ?? false, 'auto_reload' => true]);
        self::addPrimitives($env);
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
        $fns = ['include', 'ui_prim'];
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

    /** `ui_prim()` of the Twig bricks: renders a UI8Kit 3 Twig primitive of the plugin (see Primitives) */
    public static function addPrimitives(\Twig\Environment $env): void
    {
        $env->addFunction(new \Twig\TwigFunction(
            'ui_prim',
            static fn (string $name, array $args = []): string => Primitives::twig($name, $args),
            ['is_safe' => ['html']],
        ));
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
