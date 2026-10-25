<?php

declare(strict_types=1);

namespace WPFastyBuilder\Codex;

/**
 * Builder data sources derived from the Codex manifest (types/codex.manifest.json) and the WP delivery
 * (types/codex.delivery.json): one source per post kind and per taxonomy. Nothing is hard-coded per kind.
 * Source id = "<kind>s" (posts, pages, products, projects, categories, post_tags, product_cats…) — the ids
 * stored in existing landings.
 */
final class Sources
{
    private const PLATFORM = ['media', 'user'];
    private const ENTRY_CHROME = ['id', 'slug', 'locale', 'terms', 'parent_id', 'updated_at', 'created_at'];
    private const TERM_CHROME = ['id', 'name', 'slug', 'description', 'parent_id', 'locale'];

    public static function idFor(string $kind): string
    {
        return $kind === 'category' ? 'categories' : $kind . 's';
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $delivery
     * @return array<string, array<string, mixed>> id → source model
     */
    public static function fromManifest(array $manifest, array $delivery): array
    {
        $roleOf = [];
        foreach ((array) ($delivery['taxonomies'] ?? []) as $t) {
            $roleOf[(string) $t['id']] = (string) ($t['role'] ?? '');
        }
        $kinds = (array) ($delivery['wp']['kinds'] ?? []);
        $out = [];
        $taxes = [];
        foreach ((array) ($manifest['resources'] ?? []) as $res) {
            $rec = $res['record'];
            $kind = (string) $rec['id'];
            if (in_array($kind, self::PLATFORM, true) || !isset($kinds[$kind])) {
                continue;
            }
            $chrome = self::ENTRY_CHROME;
            $fields = [];
            $meta = (array) ($kinds[$kind]['meta'] ?? []);
            foreach ((array) $rec['fields'] as $f) {
                $id = (string) $f['id'];
                if (isset($meta[$id])) {
                    $rules = array_map(
                        static fn ($r): string => (string) preg_replace('/^.*\//', '', (string) $r['name']),
                        (array) ($f['rules'] ?? []),
                    );
                    $fields[$id] = [(string) $f['type'], $rules];
                } else {
                    $chrome[] = $id;
                }
            }
            $own = array_values(array_map('strval', (array) ($res['taxonomies'] ?? [])));
            $purch = $kinds[$kind]['purchasable'] ?? null;
            $out[self::idFor($kind)] = [
                'id' => self::idFor($kind), 'kind' => $kind, 'term' => false,
                'label' => (string) ($rec['label'] ?? $kind),
                'post_type' => (string) ($kinds[$kind]['post_type'] ?? $kind), 'chrome' => $chrome, 'fields' => $fields,
                'taxes' => $own, 'roleOf' => $roleOf, 'role' => null,
                'purchasable' => is_array($purch) ? $purch : null,
                'stock' => is_array($purch) ? ($purch['stock'] ?? null) : null,
            ];
            array_push($taxes, ...$own);
        }
        foreach (array_unique($taxes) as $tax) {
            $out[self::idFor($tax)] = [
                'id' => self::idFor($tax), 'kind' => $tax, 'term' => true, 'label' => $tax, 'taxonomy' => $tax,
                'chrome' => self::TERM_CHROME, 'fields' => ['count' => ['number', ['integer']]], 'taxes' => [],
                'roleOf' => $roleOf, 'role' => $roleOf[$tax] ?? null, 'purchasable' => null, 'stock' => null,
            ];
        }
        return $out;
    }
}
