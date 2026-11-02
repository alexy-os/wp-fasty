<?php

declare(strict_types=1);

namespace WPFastyBuilder\Codex;

use WPFastyBuilder\Cast;

/**
 * Builder data sources derived from the Codex manifest (types/codex.manifest.json) and the WP delivery
 * (types/codex.delivery.json): one source per post kind and per taxonomy. Nothing is hard-coded per kind.
 * Source id = "<kind>s" (posts, pages, products, projects, categories, post_tags, product_cats…) — the ids
 * stored in existing landings.
 *
 * @phpstan-type SourceModel array{id: string, kind: string, term: bool, label: string, post_type: string,
 *   taxonomy: string, chrome: list<string>, fields: array<string, array{0: string, 1: list<string>}>,
 *   taxes: list<string>, roleOf: array<string, string>, role: string|null, purchasable: array<string, mixed>|null,
 *   stock: string|null}
 */
final class Sources
{
    private const PLATFORM = ['media', 'user'];
    private const ENTRY_CHROME = ['id', 'slug', 'locale', 'terms', 'parent_id', 'updated_at', 'created_at'];
    private const TERM_CHROME = ['id', 'name', 'slug', 'description', 'parent_id', 'locale'];

    public static function idFor(string $kind): string
    {
        return 'category' === $kind ? 'categories' : $kind.'s';
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, mixed> $delivery
     *
     * @return array<string, SourceModel> id → source model
     */
    public static function fromManifest(array $manifest, array $delivery): array
    {
        $roleOf = [];
        foreach (Cast::list($delivery['taxonomies'] ?? []) as $t) {
            $roleOf[Cast::string(Cast::path($t, 'id'))] = Cast::string(Cast::path($t, 'role'));
        }
        $kinds = Cast::map(Cast::path($delivery, \WPFastyTypes\Codex\Site::WP, 'kinds'));
        $out = [];
        $taxes = [];
        foreach (Cast::list($manifest['resources'] ?? []) as $res) {
            $res = Cast::map($res);
            $rec = Cast::map($res['record'] ?? []);
            $kind = Cast::string($rec['id'] ?? '');
            if (in_array($kind, self::PLATFORM, true) || !isset($kinds[$kind])) {
                continue;
            }
            $chrome = self::ENTRY_CHROME;
            $fields = [];
            $meta = Cast::map(Cast::path($kinds, $kind, 'meta'));
            foreach (Cast::list($rec['fields'] ?? []) as $f) {
                $f = Cast::map($f);
                $id = Cast::string($f['id'] ?? '');
                if (isset($meta[$id])) {
                    $rules = array_map(
                        static fn (mixed $r): string => Cast::string(preg_replace('/^.*\//', '', Cast::string(Cast::path($r, 'name')))),
                        Cast::list($f['rules'] ?? []),
                    );
                    $fields[$id] = [Cast::string($f['type'] ?? ''), $rules];
                } else {
                    $chrome[] = $id;
                }
            }
            $own = Cast::strings($res['taxonomies'] ?? []);
            $purch = Cast::path($kinds, $kind, 'purchasable');
            $out[self::idFor($kind)] = [
                'id' => self::idFor($kind), 'kind' => $kind, 'term' => false,
                'label' => Cast::string($rec['label'] ?? $kind),
                'post_type' => Cast::string(Cast::path($kinds, $kind, 'post_type') ?? $kind), 'taxonomy' => '', 'chrome' => $chrome, 'fields' => $fields,
                'taxes' => $own, 'roleOf' => $roleOf, 'role' => null,
                'purchasable' => is_array($purch) ? Cast::map($purch) : null,
                'stock' => is_array($purch) && isset($purch['stock']) ? Cast::string($purch['stock']) : null,
            ];
            array_push($taxes, ...$own);
        }
        foreach (array_unique($taxes) as $tax) {
            $out[self::idFor($tax)] = [
                'id' => self::idFor($tax), 'kind' => $tax, 'term' => true, 'label' => $tax, 'post_type' => '', 'taxonomy' => $tax,
                'chrome' => self::TERM_CHROME, 'fields' => ['count' => ['number', ['integer']]], 'taxes' => [],
                'roleOf' => $roleOf, 'role' => $roleOf[$tax] ?? null, 'purchasable' => null, 'stock' => null,
            ];
        }

        return $out;
    }
}
