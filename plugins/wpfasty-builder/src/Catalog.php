<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Read access to a catalog.json: blocks with their variants (template, evaluated sample props, bindable paths)
 * and collections (a group of blocks, or a template set with pages).
 */
final class Catalog
{
    public const IDENT = '/^[a-z][a-z0-9_-]{0,62}$/';
    /** a variant template of either runtime; a catalog uses only its own runtime's extension (see check()) */
    public const TEMPLATE = '#^blocks/[a-z0-9][a-z0-9-]*(\.latte|\.html\.twig)$#';
    public const RUNTIMES = ['latte' => '.latte', 'twig' => '.html.twig'];

    /** @var array<string, array<string, mixed>> */
    private array $blocks = [];
    /** @var array<string, array<string, mixed>> */
    private array $collections = [];

    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data)
    {
        foreach ((array) ($data['blocks'] ?? []) as $b) {
            if (is_array($b) && is_string($b['id'] ?? null)) {
                $this->blocks[$b['id']] = $b;
            }
        }
        foreach ((array) ($data['collections'] ?? []) as $c) {
            if (is_array($c) && is_string($c['id'] ?? null)) {
                $this->collections[$c['id']] = $c;
            }
        }
    }

    /**
     * Shape errors of a decoded catalog (empty when it is usable).
     *
     * @param array<string, mixed>|null $c
     * @return list<string>
     */
    public static function check(?array $c): array
    {
        if ($c === null) {
            return ['not a JSON object'];
        }
        $errors = [];
        $runtime = self::runtimeOf($c);
        if ($runtime === null) {
            $errors[] = 'templates must be "latte" or "twig" (and equal runtime)';
            return $errors;
        }
        $ext = self::RUNTIMES[$runtime];
        if (!is_array($c['blocks'] ?? null) || $c['blocks'] === []) {
            $errors[] = 'blocks must be a non-empty list';
            return $errors;
        }
        foreach ($c['blocks'] as $i => $b) {
            if (!is_array($b) || !is_string($b['id'] ?? null) || preg_match('/^[a-z][a-z0-9_-]*$/i', $b['id']) !== 1) {
                $errors[] = "blocks[$i].id is invalid";
                continue;
            }
            foreach ((array) ($b['variants'] ?? []) as $j => $v) {
                $template = is_array($v) && is_string($v['template'] ?? null) ? $v['template'] : '';
                $ok = preg_match(self::TEMPLATE, $template) === 1 && str_ends_with($template, $ext);
                if (!is_string($v['id'] ?? null) || !$ok) {
                    $errors[] = "blocks[$i].variants[$j] needs an id and a template blocks/<name>$ext";
                } elseif (!is_array($v['props'] ?? null)) {
                    $errors[] = "blocks[$i].variants[$j].props must be an object";
                }
            }
        }
        foreach ((array) ($c['collections'] ?? []) as $i => $col) {
            if (!is_array($col) || !is_string($col['id'] ?? null) || !is_array($col['blocks'] ?? null)) {
                $errors[] = "collections[$i] needs an id and blocks";
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
     * @return array<string, mixed>
     */
    public static function forRuntime(array $c, string $runtime): array
    {
        if ((int) ($c['format'] ?? 1) >= 2) {
            return $c;
        }
        unset($c['engines']);
        $c['format'] = 2;
        $c['runtime'] = $runtime;
        $c['templates'] = $runtime;
        foreach ((array) ($c['blocks'] ?? []) as $i => $b) {
            foreach ((array) ($b['variants'] ?? []) as $j => $v) {
                if (is_array($v) && is_string($v['template'] ?? null)) {
                    $c['blocks'][$i]['variants'][$j]['template']
                        = preg_replace('/\.latte$/', self::RUNTIMES[$runtime], $v['template']);
                }
            }
        }
        return $c;
    }

    /**
     * Variant templates of a catalog missing under $root (a runtime folder with blocks/).
     *
     * @param array<string, mixed> $c
     * @return list<string>
     */
    public static function missingTemplates(array $c, string $root): array
    {
        $missing = [];
        foreach ((array) ($c['blocks'] ?? []) as $b) {
            foreach ((array) ($b['variants'] ?? []) as $v) {
                $t = is_array($v) ? (string) ($v['template'] ?? '') : '';
                if ($t !== '' && !is_file($root . '/' . $t)) {
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
        unset($out['blocks'], $out['collections']);
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
                return $v;
            }
        }
        return null;
    }

    /** @return array<string, mixed>|null */
    public function collection(string $id): ?array
    {
        return $this->collections[$id] ?? null;
    }

    /** @return list<array{id: string, label: string, type: string, blocks: int}> */
    public function collections(): array
    {
        $out = [];
        foreach ($this->collections as $c) {
            $out[] = [
                'id' => (string) $c['id'],
                'label' => (string) ($c['label'] ?? $c['id']),
                'type' => (string) ($c['type'] ?? 'group'),
                'blocks' => count((array) $c['blocks']),
            ];
        }
        return $out;
    }

    /**
     * The data kind of every bindable path of a variant: "posts" => "post", "filter.topics" => "topic".
     *
     * @return array<string, string>
     */
    public function bindable(string $block, string $variant): array
    {
        $b = $this->block($block);
        $v = $this->variant($block, $variant);
        if ($b === null || $v === null) {
            return [];
        }
        $kinds = [];
        foreach ((array) ($b['data'] ?? []) as $d) {
            if (is_array($d) && is_string($d['as'] ?? null)) {
                $kinds[$d['as']] = (string) ($d['kind'] ?? '');
            }
        }
        $out = [];
        foreach ((array) ($v['bindings'] ?? []) as $bind) {
            if (is_array($bind) && is_array($bind['path'] ?? null)) {
                $out[implode('.', $bind['path'])] = $kinds[(string) ($bind['data'] ?? '')] ?? '';
            }
        }
        return $out;
    }

    /**
     * The blocks the editor offers: those of a collection (with its pages), or all of them.
     *
     * @return array{
     *   meta: array<string, mixed>,
     *   collection: array<string, mixed>|null,
     *   blocks: list<array<string, mixed>>
     * }
     */
    public function forEditor(?string $collection): array
    {
        $col = $collection !== null ? $this->collection($collection) : null;
        $ids = $col !== null ? array_values(array_unique(array_map(
            'strval',
            (array) $col['blocks'],
        ))) : array_keys($this->blocks);
        $blocks = [];
        foreach ($ids as $id) {
            if (isset($this->blocks[$id])) {
                $blocks[] = $this->blocks[$id];
            }
        }
        return ['meta' => $this->meta(), 'collection' => $col, 'blocks' => $blocks];
    }
}
