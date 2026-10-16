<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use Latte\Engine;
use UI8Kit\Latte\Extension;
use WPFastyBuilder\Source\SourceInterface;

/**
 * Renders one block template of a source with its props ($p) on a Latte engine of its own: the codegen-x
 * ui_* functions are registered there, so the theme engine only needs the single wpfb_block() function.
 * The PHP runtime always comes from the bundled ui8kit source; templates of remote sources compile through
 * TemplateGuard, an allowlist of the tags, filters and functions the generated templates use.
 */
final class BlockRenderer
{
    /** @var array<string, Engine> */
    private array $engines = [];

    /** @param array<string, SourceInterface> $sources */
    public function __construct(private readonly array $sources, private readonly ?string $cacheDir)
    {
        self::loadRuntime();
    }

    public static function loadRuntime(): void
    {
        if (class_exists(Extension::class, false)) {
            return;
        }
        $php = dirname(__DIR__) . '/sources/ui8kit/latte/php';
        foreach (['CnMerge', 'Runtime', 'Extension'] as $f) {
            if (is_file("$php/$f.php")) {
                require_once "$php/$f.php";
            }
        }
    }

    /** @param array<string, mixed> $props */
    public function render(string $source, string $template, array $props): string
    {
        if (preg_match(Catalog::TEMPLATE, $template) !== 1) {
            return '';
        }
        $engine = $this->engine($source);
        if ($engine === null) {
            return '';
        }
        try {
            return $engine->renderToString($template, ['p' => $props]);
        } catch (\Throwable $e) {
            $msg = function_exists('current_user_can') && current_user_can('edit_pages') ? $e->getMessage() : '';
            return '<!-- wpfasty-builder: ' . htmlspecialchars($template . ' ' . $msg, ENT_QUOTES) . ' -->';
        }
    }

    public function engine(string $source): ?Engine
    {
        if (isset($this->engines[$source])) {
            return $this->engines[$source];
        }
        $src = $this->sources[$source] ?? null;
        if ($src === null || !is_dir($src->templateRoot() . '/blocks')) {
            return null;
        }
        $tmp = $this->cacheDir !== null ? $this->cacheDir . '/' . $source : null;
        if ($tmp !== null && !is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }
        $engine = Extension::engine($src->templateRoot(), $tmp !== null && is_dir($tmp) ? $tmp : null);
        if ($src->sandboxed()) {
            $engine->addExtension(self::guard($engine));
        }
        return $this->engines[$source] = $engine;
    }

    public static function guard(Engine $engine): TemplateGuard
    {
        $fns = [];
        foreach (array_keys($engine->getFunctions()) as $name) {
            if (str_starts_with((string) $name, 'ui_')) {
                $fns[] = (string) $name;
            }
        }
        return new TemplateGuard($fns);
    }
}
