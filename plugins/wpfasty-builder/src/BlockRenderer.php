<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WPFastyBuilder\Source\SourceInterface;

/**
 * Renders one block template of a source with its props ($p), in the runtime of the template name:
 * blocks/<name>.latte on a Latte engine of its own (the codegen-x og_* functions are registered there), and
 * blocks/<name>.html.twig on a Twig environment over the source's Twig package. Blocks render through the
 * compositions (compose/) over the UI8Kit 3 primitives (ui/) of the same package. A source serves ONE runtime
 * (SourceInterface::runtime()); a template of the other runtime, or one missing from the source, renders nothing and
 * is reported (action `wpfasty_builder_missing_block`) — never through the other engine (ADR-001, K7). Each engine is
 * created only when a template of its runtime renders. The PHP runtimes come from the plugin build of that runtime;
 * templates of remote sources render sandboxed: Latte through TemplateGuard, Twig through Twig's SandboxExtension;
 * their compose/, ui/ and icons/ layers always come from the bundled package (SourceLoader, TwigSourceLoader).
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

    /** the bundled package of a runtime (the trusted compose/ui/icons layers of sandboxed sources) */
    public static function bundled(string $runtime): string
    {
        return dirname(__DIR__) . '/sources/ui8kit/' . $runtime;
    }

    /** loads the codegen-x runtime of the bundled package: UI8Kit 3 primitives, compositions and og_* organisms */
    private static function loadPackage(string $runtime, string $probe): bool
    {
        if (class_exists($probe, false)) {
            return true;
        }
        if (!Primitives::loadRuntime($runtime)) {
            return false;
        }
        $root = self::bundled($runtime);
        // compose/*.php declare CodegenX\Compose\{Latte,Twig}\<file>; the parent theme ships the same classes
        // (ui8kit/compose, twig/ui8kit/compose: kit-compose form fields, same commit and compose.json), so a class
        // already defined or autoloadable is used, not redeclared (like Primitives::loadRuntime)
        $ns = $runtime === 'twig' ? 'CodegenX\\Compose\\Twig\\' : 'CodegenX\\Compose\\Latte\\';
        $files = array_values(array_filter(
            glob($root . '/compose/*.php') ?: [],
            static fn (string $f): bool => !class_exists($ns . basename($f, '.php'))
        ));
        foreach (['CnMerge', 'Runtime', 'OrganismExtension'] as $f) {
            $files[] = "$root/php/$f.php";
        }
        foreach ($files as $file) {
            if (!is_file($file)) {
                return false;
            }
            require_once $file;
        }
        return class_exists($probe, false);
    }

    /** loads the Latte runtime of the bundled source (needs latte/latte) */
    public static function loadRuntime(): bool
    {
        return class_exists('\Latte\Engine')
            && self::loadPackage('latte', '\CodegenX\Organisms\Latte\OrganismExtension');
    }

    /** loads the Twig runtime of the bundled source (needs twig/twig) */
    public static function loadTwigRuntime(): bool
    {
        return class_exists('\Twig\Environment')
            && self::loadPackage('twig', '\CodegenX\Organisms\Twig\OrganismExtension');
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
        if (!self::loadRuntime()) {
            return null;
        }
        $root = $src->templateRoot();
        $bundled = $src->sandboxed() ? self::bundled('latte') : null;
        $loader = new SourceLoader($root, $bundled);
        $engine = new \Latte\Engine();
        $tmp = $this->cache($source);
        if ($tmp !== null) {
            $engine->setTempDirectory($tmp);
        }
        $engine->setLoader($loader);
        // recipes, presets and icons (JSON) of a sandboxed source come from the bundled package too
        $engine->addExtension(new \CodegenX\Organisms\Latte\OrganismExtension($bundled ?? $root, $engine));
        if ($bundled !== null) {
            $engine->addExtension(self::guard($engine, $loader));
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
        $bundled = $src->sandboxed() ? self::bundled('twig') : null;
        $loader = $bundled !== null
            ? new TwigSourceLoader($root, $bundled)
            : new \Twig\Loader\FilesystemLoader([$root]);
        $env = new \Twig\Environment($loader, [
            'autoescape' => 'html', 'strict_variables' => false, 'cache' => $tmp ?? false, 'auto_reload' => true,
        ]);
        $env->addExtension(new \UI8Kit\Twig\TwigExtension());
        $env->addExtension(new \CodegenX\Compose\Twig\ComposeExtension());
        $env->addExtension(new \CodegenX\Organisms\Twig\OrganismExtension($bundled ?? $root));
        if ($loader instanceof TwigSourceLoader) {
            $env->addExtension(self::sandbox($env, $loader));
        }
        return $this->twigs[$source] = $env;
    }

    /** the Latte guard of a sandboxed source: the og_* functions; the trusted layers of $loader are not checked */
    public static function guard(\Latte\Engine $engine, ?SourceLoader $loader = null): TemplateGuard
    {
        $fns = [];
        foreach (array_keys($engine->getFunctions()) as $name) {
            if (str_starts_with((string) $name, 'og_')) {
                $fns[] = (string) $name;
            }
        }
        return new TemplateGuard($fns, $loader);
    }

    /**
     * Twig sandbox of a remote source: its blocks may use only the tags, filters and og_* functions of the generated
     * templates and no methods/properties; the bundled compose/ui/icons layers are trusted (the loader's source policy)
     */
    public static function sandbox(
        \Twig\Environment $env,
        ?\Twig\Sandbox\SourcePolicyInterface $policy = null,
    ): \Twig\Extension\SandboxExtension {
        $fns = ['include'];
        foreach ($env->getExtension(\CodegenX\Organisms\Twig\OrganismExtension::class)->getFunctions() as $f) {
            $fns[] = $f->getName();
        }
        $security = new \Twig\Sandbox\SecurityPolicy(
            ['if', 'for', 'set'],
            ['escape', 'e', 'raw', 'merge'],
            [],
            [],
            $fns,
        );
        return $policy !== null
            ? new \Twig\Extension\SandboxExtension($security, false, $policy)
            : new \Twig\Extension\SandboxExtension($security, true);
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
