<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * The Latte loader of a source: blocks/<name>.latte come from the source's own root, and for a sandboxed (remote)
 * source the shared layers — the compositions (compose/), the UI8Kit primitives (ui/) and the icons (icons/) — come
 * from the plugin's bundled package instead. Those trusted layers use static calls and {include}, which the guard
 * forbids, so `trusted` tells TemplateGuard whether the template being compiled is one of them (Engine::compile reads
 * the content right before it parses and runs the passes).
 */
final class SourceLoader extends \Latte\Loaders\FileLoader
{
    public const SHARED = ['compose/', 'ui/', 'icons/'];

    public bool $trusted = false;

    private readonly string $root;

    public function __construct(string $root, private readonly ?string $bundled = null)
    {
        parent::__construct($root);
        $this->root = (string) $this->baseDir;
    }

    public static function shared(string $name): bool
    {
        foreach (self::SHARED as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }
        return false;
    }

    public function getContent(string $fileName): string
    {
        $name = self::normalizePath($fileName);
        $this->trusted = $this->bundled !== null && self::shared($name);
        if (!$this->trusted) {
            $this->baseDir = $this->root;
            return parent::getContent($fileName);
        }
        $this->baseDir = self::normalizePath($this->bundled . '/');
        try {
            return parent::getContent($name);
        } finally {
            $this->baseDir = $this->root;
        }
    }

    public function getUniqueId(string $file): string
    {
        $name = self::normalizePath($file);
        return $this->bundled !== null && self::shared($name)
            ? self::normalizePath($this->bundled . '/') . $name
            : parent::getUniqueId($file);
    }
}
