<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Read access to a catalog.json: blocks with their variants (template, evaluated sample props). No data bindings
 * (ADR-007): a variant renders its own props.
 */
final class Catalog
{
    public const IDENT = '/^[a-z][a-z0-9_-]{0,62}$/';
    /** a variant template of either runtime; a catalog uses only its own runtime's extension (see check()) */
    public const TEMPLATE = '#^blocks/[a-z0-9][a-z0-9-]*(\.latte|\.html\.twig)$#';
    public const RUNTIMES = ['latte' => '.latte', 'twig' => '.html.twig'];

    /** @var array<string, array<string, mixed>> */
    private array $blocks = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
        foreach ((array) ($data['blocks'] ?? []) as $b) {
            if (is_array($b) && is_string($b['id'] ?? null)) {
                $this->blocks[$b['id']] = Cast::map($b);
            }
        }
    }

    /**
     * Shape errors of a decoded catalog (empty when it is usable).
     *
     * @param array<string, mixed>|null $c
     *
     * @return list<string>
     */
    public static function check(?array $c): array
    {
        if (null === $c) {
            return ['not a JSON object'];
        }
        $errors = [];
        $runtime = self::runtimeOf($c);
        if (null === $runtime) {
            $errors[] = 'templates must be "latte" or "twig" (and equal runtime)';

            return $errors;
        }
        $ext = self::RUNTIMES[$runtime];
        if (!is_array($c['blocks'] ?? null) || [] === $c['blocks']) {
            $errors[] = 'blocks must be a non-empty list';

            return $errors;
        }
        foreach ($c['blocks'] as $i => $b) {
            if (!is_array($b) || !is_string($b['id'] ?? null) || 1 !== preg_match('/^[a-z][a-z0-9_-]*$/i', $b['id'])) {
                $errors[] = "blocks[$i].id is invalid";
                continue;
            }
            foreach ((array) ($b['variants'] ?? []) as $j => $v) {
                $v = Cast::map($v);
                $template = is_string($v['template'] ?? null) ? $v['template'] : '';
                $ok = 1 === preg_match(self::TEMPLATE, $template) && str_ends_with($template, $ext);
                if (!is_string($v['id'] ?? null) || !$ok) {
                    $errors[] = "blocks[$i].variants[$j] needs an id and a template blocks/<name>$ext";
                } elseif (!is_array($v['props'] ?? null)) {
                    $errors[] = "blocks[$i].variants[$j].props must be an object";
                }
            }
        }

        return $errors;
    }

    /**
     * The runtime of a decoded catalog: `runtime` (format 2) must equal `templates`; format 1 has only
     * `templates: latte`. Null when missing or inconsistent.
     *
     * @param array<string, mixed> $c
     */
    public static function runtimeOf(array $c): ?string
    {
        $templates = $c['templates'] ?? null;
        $runtime = $c['runtime'] ?? $templates;

        return is_string($runtime) && $runtime === $templates && isset(self::RUNTIMES[$runtime]) ? $runtime : null;
    }

    /**
     * A format-1 catalog (one catalog, `.latte` template names, Latte package next to an optional Twig one) as the
     * format-2 catalog of $runtime: same blocks, the runtime's template extension. Format 2 is returned unchanged.
     *
     * @param array<string, mixed> $c
     *
     * @return array<string, mixed>
     */
    public static function forRuntime(array $c, string $runtime): array
    {
        if (Cast::int($c['format'] ?? 1) >= 2) {
            return $c;
        }
        unset($c['engines']);
        $c['format'] = 2;
        $c['runtime'] = $runtime;
        $c['templates'] = $runtime;
        $blocks = (array) ($c['blocks'] ?? []);
        foreach ($blocks as $i => $b) {
            if (!is_array($b)) {
                continue;
            }
            $variants = (array) ($b['variants'] ?? []);
            foreach ($variants as $j => $v) {
                if (is_array($v) && is_string($v['template'] ?? null)) {
                    $v['template'] = preg_replace('/\.latte$/', self::RUNTIMES[$runtime], $v['template']);
                    $variants[$j] = $v;
                }
            }
            $b['variants'] = $variants;
            $blocks[$i] = $b;
        }
        if (isset($c['blocks'])) {
            $c['blocks'] = $blocks;
        }

        return $c;
    }

    /**
     * Variant templates of a catalog missing under $root (a runtime folder with blocks/).
     *
     * @param array<string, mixed> $c
     *
     * @return list<string>
     */
    public static function missingTemplates(array $c, string $root): array
    {
        $missing = [];
        foreach ((array) ($c['blocks'] ?? []) as $b) {
            foreach (is_array($b) ? (array) ($b['variants'] ?? []) : [] as $v) {
                $t = is_array($v) ? Cast::string($v['template'] ?? '') : '';
                if ('' !== $t && !is_file($root.'/'.$t)) {
                    $missing[$t] = true;
                }
            }
        }

        return array_keys($missing);
    }

    public function runtime(): ?string
    {
        return self::runtimeOf($this->data);
    }

    /** @return array<string, mixed> */
    public function meta(): array
    {
        $out = $this->data;
        unset($out['blocks'], $out['collections']); // collections: ignored key of older registries

        return $out;
    }

    /** @return array<string, mixed>|null */
    public function block(string $id): ?array
    {
        return $this->blocks[$id] ?? null;
    }

    /** @return array<string, mixed>|null */
    public function variant(string $block, string $variant): ?array
    {
        foreach ((array) ($this->blocks[$block]['variants'] ?? []) as $v) {
            if (is_array($v) && ($v['id'] ?? null) === $variant) {
                return Cast::map($v);
            }
        }

        return null;
    }

    /**
     * The blocks the editor offers: every block of the catalog, with the catalog's meta.
     *
     * @return array{meta: array<string, mixed>, blocks: list<array<string, mixed>>}
     */
    public function forEditor(): array
    {
        return ['meta' => $this->meta(), 'blocks' => array_values($this->blocks)];
    }
}
