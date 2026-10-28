<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * A page layout as a @ui8kit/contract v0.4 entry of kind "layout":
 *   { id, locale, kind: "layout", status, visibility, slug, title, version, created_at, updated_at,
 *     metadata: { source: {value}, collection: {value}, page_id: {value}, blocks: {value: [ {id, block, variant,
 * props, bindings} ]} } }
 * `props` are overrides of the variant sample props; `bindings` map a bindable path ("posts", "filter.topics")
 * to a data source {source, limit, term, sale}. normalize() sanitizes untrusted input and reports contract issues.
 */
final class Layout
{
    public const KIND = 'layout';
    public const MAX_BLOCKS = 40;
    public const MAX_DEPTH = 8;
    public const STATUSES = ['draft', 'published'];
    private const LOCALE = '/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/';
    private const RFC3339 = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/';

    /**
     * Sanitizes a layout payload against a catalog and the known data sources.
     *
     * @param array<string, mixed> $in        untrusted payload: {source, collection, blocks:[...]}, or a full entry
     * @param list<string>         $dataSources ids accepted in bindings
     * @param array<string, mixed> $base      id, locale, slug, title, status, version, created_at, updated_at, page_id
     * @return array{0: array<string, mixed>, 1: list<array{code: string, path: string, message: string}>}
     */
    public static function normalize(
        array $in,
        string $sourceId,
        Catalog $catalog,
        array $dataSources,
        array $base,
    ): array {
        $issues = [];
        $meta = is_array($in['metadata'] ?? null) ? $in['metadata'] : null;
        $blocksIn = $meta !== null ? ($meta['blocks']['value'] ?? []) : ($in['blocks'] ?? []);
        $collection = $meta !== null ? ($meta['collection']['value'] ?? '') : ($in['collection'] ?? '');
        $blocks = [];
        $seen = [];
        foreach (array_slice(is_array($blocksIn) ? array_values($blocksIn) : [], 0, self::MAX_BLOCKS) as $i => $b) {
            $p = "metadata.blocks.value[$i]";
            if (!is_array($b)) {
                $issues[] = self::issue('layout.block_invalid', $p, 'block must be an object');
                continue;
            }
            $block = is_string($b['block'] ?? null) ? $b['block'] : '';
            $variant = is_string($b['variant'] ?? null) ? $b['variant'] : '';
            if ($catalog->variant($block, $variant) === null) {
                $issues[] = self::issue('layout.block_unknown', $p, "unknown block/variant $block/$variant");
                continue;
            }
            $id = is_string($b['id'] ?? null) && preg_match(Catalog::IDENT, $b['id']) === 1 ? $b['id'] : 'b' . ($i + 1);
            while (isset($seen[$id])) {
                $id .= 'x';
            }
            $seen[$id] = true;
            $bindable = $catalog->bindable($block, $variant);
            $bindings = [];
            foreach ((array) ($b['bindings'] ?? []) as $path => $bind) {
                $path = (string) $path;
                if (!isset($bindable[$path])) {
                    $issues[] = self::issue(
                        'layout.binding_path_unknown',
                        "$p.bindings.$path",
                        "$path is not bindable in $block/$variant",
                    );
                    continue;
                }
                $src = is_array($bind) && is_string($bind['source'] ?? null) ? $bind['source'] : '';
                if (!in_array($src, $dataSources, true)) {
                    $issues[] = self::issue(
                        'layout.binding_source_unknown',
                        "$p.bindings.$path",
                        "unknown data source \"$src\"",
                    );
                    continue;
                }
                $limit = max(1, min(24, (int) ($bind['limit'] ?? 6)));
                $one = ['source' => $src, 'limit' => $limit];
                $term = is_string($bind['term'] ?? null) ? strtolower((string) preg_replace(
                    '/[^a-z0-9_-]/i',
                    '',
                    $bind['term'],
                )) : '';
                if ($term !== '') {
                    $one['term'] = $term;
                }
                if (($bind['sale'] ?? false) === true) {
                    $one['sale'] = true;
                }
                $map = self::map($bind['map'] ?? null);
                if ($map !== []) {
                    $one['map'] = $map;
                }
                $bindings[$path] = $one;
            }
            $sample = (array) ($catalog->variant($block, $variant)['props'] ?? []);
            $props = self::clean(is_array($b['props'] ?? null) ? $b['props'] : [], 0, $sample, $issues, "$p.props");
            // bound paths and the paths the current record fills (richtext title, content…) are never layout props
            foreach ([...array_keys($bindings), ...array_keys($catalog->current($block, $variant))] as $path) {
                self::unset($props, explode('.', (string) $path));
            }
            $blocks[] = [
                'id' => $id,
                'block' => $block,
                'variant' => $variant,
                'props' => (object) $props,
                'bindings' => (object) $bindings,
            ];
        }
        if (is_array($blocksIn) && count($blocksIn) > self::MAX_BLOCKS) {
            $issues[] = self::issue(
                'layout.too_many_blocks',
                'metadata.blocks.value',
                'at most ' . self::MAX_BLOCKS . ' blocks',
            );
        }
        $known = is_string($collection) && ($collection === '' || $catalog->collection($collection) !== null);
        $collection = $known ? $collection : '';
        $entry = [
            'id' => (string) ($base['id'] ?? ''),
            'locale' => (string) ($base['locale'] ?? 'en'),
            'kind' => self::KIND,
            'status' => in_array($base['status'] ?? '', self::STATUSES, true) ? $base['status'] : 'draft',
            'visibility' => 'public',
            'slug' => (string) ($base['slug'] ?? ''),
            'title' => (string) ($base['title'] ?? ''),
            'metadata' => [
                'source' => ['value' => $sourceId],
                'collection' => ['value' => $collection],
                'page_id' => ['value' => (string) ($base['page_id'] ?? '')],
                'blocks' => ['value' => $blocks],
            ],
            'version' => max(1, (int) ($base['version'] ?? 1)),
            'created_at' => (string) ($base['created_at'] ?? ''),
            'updated_at' => (string) ($base['updated_at'] ?? ''),
        ];
        return [$entry, array_merge($issues, self::check($entry))];
    }

    /**
     * The core checks of a v0.4 entry (ui8kit/contract src/validate-v04.ts checkEntryV04) for the fields a layout uses.
     *
     * @param array<string, mixed> $e
     * @return list<array{code: string, path: string, message: string}>
     */
    public static function check(array $e): array
    {
        $out = [];
        if (trim((string) ($e['id'] ?? '')) === '') {
            $out[] = self::issue('core.id_required', 'id', 'id is required');
        }
        if (preg_match(self::LOCALE, (string) ($e['locale'] ?? '')) !== 1) {
            $out[] = self::issue('core.locale_invalid', 'locale', 'locale must be a BCP 47 tag');
        }
        if (!is_int($e['version'] ?? null) || $e['version'] < 1) {
            $out[] = self::issue('core.version_required', 'version', 'version must be an integer >= 1');
        }
        $c = (string) ($e['created_at'] ?? '');
        $u = (string) ($e['updated_at'] ?? '');
        if (preg_match(self::RFC3339, $c) !== 1 || preg_match(self::RFC3339, $u) !== 1) {
            $out[] = self::issue(
                'core.timestamps_required',
                'created_at',
                'created_at and updated_at must be RFC 3339',
            );
        } elseif (strtotime($u) < strtotime($c)) {
            $out[] = self::issue('core.timestamps_order', 'updated_at', 'updated_at precedes created_at');
        }
        if (preg_match(Catalog::IDENT, (string) ($e['kind'] ?? '')) !== 1) {
            $out[] = self::issue('core.entry.kind_invalid', 'kind', 'kind is invalid');
        }
        if (!in_array(
            $e['status'] ?? '',
            ['draft', 'pending', 'scheduled', 'published', 'archived', 'trashed'],
            true,
        )) {
            $out[] = self::issue('core.entry.status_invalid', 'status', 'status is invalid');
        }
        if (($e['visibility'] ?? '') !== 'public' && ($e['visibility'] ?? '') !== 'private') {
            $out[] = self::issue('core.entry.visibility_invalid', 'visibility', 'visibility must be public or private');
        }
        if (trim((string) ($e['slug'] ?? '')) === '') {
            $out[] = self::issue('core.entry.slug_required', 'slug', 'slug is required');
        }
        if (!is_string($e['title'] ?? null)) {
            $out[] = self::issue('core.entry.title_required', 'title', 'title is required');
        }
        foreach ((array) ($e['metadata'] ?? []) as $k => $mv) {
            if (!is_array($mv) || !array_key_exists('value', $mv)) {
                $out[] = self::issue(
                    'core.metadata_invalid',
                    "metadata.$k",
                    'metadata value must be {value, private?}',
                );
            }
        }
        return $out;
    }

    /**
     * The manifest resource of the layout kind (@ui8kit/contract v0.4 manifest).
     *
     * @return array<string, mixed>
     */
    public static function manifest(): array
    {
        return [
            'name' => 'wpfasty-builder',
            'version' => '0.4.0',
            'resources' => [[
                'record' => [
                    'id' => self::KIND,
                    'label' => 'Layout',
                    'scope' => 'workspace',
                    'description' => 'a page built from catalog blocks; rendered as a Latte template',
                    'fields' => [
                        ['id' => 'source', 'label' => 'Source', 'type' => 'string', 'required' => true],
                        ['id' => 'collection', 'label' => 'Collection', 'type' => 'string'],
                        ['id' => 'page_id', 'label' => 'Page', 'type' => 'string', 'required' => true],
                        ['id' => 'blocks', 'label' => 'Blocks', 'type' => 'collection', 'required' => true, 'items' => [
                            'id' => 'item', 'label' => 'Block', 'type' => 'object', 'fields' => [
                                ['id' => 'id', 'label' => 'Id', 'type' => 'string', 'required' => true],
                                ['id' => 'block', 'label' => 'Block', 'type' => 'string', 'required' => true],
                                ['id' => 'variant', 'label' => 'Variant', 'type' => 'string', 'required' => true],
                                ['id' => 'props', 'label' => 'Props', 'type' => 'json'],
                                ['id' => 'bindings', 'label' => 'Bindings', 'type' => 'json'],
                            ],
                        ]],
                    ],
                ],
            ]],
        ];
    }

    /** @return list<array<string, mixed>> the blocks of an entry as arrays */
    public static function blocks(array $entry): array
    {
        $list = $entry['metadata']['blocks']['value'] ?? [];
        $out = [];
        foreach (is_array($list) ? $list : [] as $b) {
            $b = json_decode((string) json_encode($b), true);
            if (is_array($b)) {
                $b['props'] = is_array($b['props'] ?? null) ? $b['props'] : [];
                $b['bindings'] = is_array($b['bindings'] ?? null) ? $b['bindings'] : [];
                $out[] = $b;
            }
        }
        return $out;
    }

    /**
     * Variant sample props with the overrides on top (objects merge, lists and scalars replace).
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $over
     * @return array<string, mixed>
     */
    public static function merge(array $base, array $over): array
    {
        foreach ($over as $k => $v) {
            if (is_array($v) && !array_is_list($v) && is_array($base[$k] ?? null) && !array_is_list($base[$k])) {
                $base[$k] = self::merge($base[$k], $v);
            } else {
                $base[$k] = $v;
            }
        }
        return $base;
    }

    /** @param array<string, mixed> $a */
    public static function set(array &$a, array $path, mixed $value): void
    {
        $k = array_shift($path);
        if ($path === []) {
            $a[$k] = $value;
            return;
        }
        if (!is_array($a[$k] ?? null)) {
            $a[$k] = [];
        }
        self::set($a[$k], $path, $value);
    }

    /** @param array<string, mixed> $a */
    private static function unset(array &$a, array $path): void
    {
        $k = array_shift($path);
        if ($path === []) {
            unset($a[$k]);
        } elseif (is_array($a[$k] ?? null)) {
            self::unset($a[$k], $path);
        }
    }

    /**
     * Plain JSON values only; strings without tags and control characters, at most 2000 characters; hrefs limited to
     * safe schemes (safeHref), every other URL prop (Safe::URL_KEY: src, srcset, action, poster, url, image…) to a
     * relative URL or an allowed scheme (Safe::url); a value whose JSON type differs from the variant sample at the
     * same path (string / number / boolean / list / object; the catalog schema of the props) is dropped with an issue,
     * so a template never gets an object where it prints a string. No `__html` key at any depth.
     *
     * @param array<mixed> $v
     * @param mixed        $sample the variant sample props at the same path (null: no constraint)
     * @param list<array{code: string, path: string, message: string}> $issues
     * @return array<mixed>
     */
    private static function clean(
        array $v,
        int $depth,
        mixed $sample = null,
        array &$issues = [],
        string $at = 'props',
        string $parent = '',
    ): array {
        $out = [];
        if ($depth > self::MAX_DEPTH) {
            $issues[] = self::issue('layout.props_too_deep', $at, 'props nest at most ' . self::MAX_DEPTH . ' levels');
            return $out;
        }
        $list = array_is_list($v);
        foreach ($v as $k => $x) {
            $key = is_int($k) ? $k : (string) preg_replace('/[^A-Za-z0-9_.-]/', '', (string) $k);
            // `__html` is the trusted-markup marker (TrustedHtml): only the data adapter sets it, never a layout
            if ($key === '' || $key === '__html') {
                continue;
            }
            $name = is_string($key) ? $key : $parent; // list items are checked like the list's key ("images" → src)
            $want = null;
            if (is_array($sample)) {
                $want = $list && array_is_list($sample) ? ($sample[0] ?? null) : ($sample[$key] ?? null);
            }
            $path = $at . '.' . $key;
            if (!self::sameKind($want, $x)) {
                $issues[] = self::issue(
                    'layout.prop_type',
                    $path,
                    $path . ' must be ' . self::kind($want) . ', not ' . self::kind($x),
                );
                continue;
            }
            if (is_array($x)) {
                $out[$key] = self::clean($x, $depth + 1, $want, $issues, $path, $name);
            } elseif (is_string($x)) {
                $s = trim(Safe::text(strip_tags(mb_substr($x, 0, 2000))));
                if (preg_match('/href$/i', $name) === 1) {
                    $s = self::safeHref($s) ? $s : '#/';
                } elseif (Safe::isUrlKey($name) && preg_match(Links::TOKEN, $s) !== 1) {
                    $s = Safe::url($s, preg_match(Safe::IMAGE_KEY, $name) === 1);
                }
                $out[$key] = $s;
            } elseif (is_int($x) || is_float($x) || is_bool($x) || $x === null) {
                $out[$key] = is_float($x) && !is_finite($x) ? 0 : $x;
            }
        }
        return $out;
    }

    /** $x may stand where the sample has $want: same JSON type; null (unset) and an empty array fit any shape */
    private static function sameKind(mixed $want, mixed $x): bool
    {
        if ($want === null || $x === null) {
            return true;
        }
        $a = self::kind($want);
        $b = self::kind($x);
        return $a === $b || (is_array($want) && is_array($x) && ($want === [] || $x === []));
    }

    /** JSON type of a prop value: string, number, boolean, list, object, null, other */
    private static function kind(mixed $v): string
    {
        return match (true) {
            is_string($v) => 'string',
            is_int($v) || is_float($v) => 'number',
            is_bool($v) => 'boolean',
            $v === null => 'null',
            is_array($v) => $v === [] || array_is_list($v) ? 'list' : 'object',
            default => 'other',
        };
    }

    /**
     * Props without trusted markup: every TrustedHtml marker ({"__html": "…"}) becomes its plain text, which the
     * templates escape. Applied to the props of a catalog variant and a layout; trusted markup reaches a block only
     * through the current record of the data adapter (DataSources::current), filled after this.
     *
     * @param array<array-key, mixed> $v
     * @return array<array-key, mixed>
     */
    public static function untrust(array $v): array
    {
        foreach ($v as $k => $x) {
            if (is_array($x)) {
                $v[$k] = array_key_exists('__html', $x) ? (is_scalar($x['__html']) ? (string) $x['__html'] : '')
                    : self::untrust($x);
            }
        }
        return $v;
    }

    public static function safeHref(string $h): bool
    {
        return $h === '' || preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $h) === 1
            || (preg_match(Links::TOKEN, $h, $m, PREG_OFFSET_CAPTURE) === 1 && $m[0][1] === 0
                && !in_array($m[1][0], ['open-now', 'price', 'stock'], true)); // text tokens are never a link
    }

    /** @return array{code: string, path: string, message: string} */
    private static function issue(string $code, string $path, string $message): array
    {
        return ['code' => $code, 'path' => $path, 'message' => $message];
    }

    /** readPath grammar of a manual field mapping (record field → path[|pipe[:arg]]) */
    public const MAP_PATH = '/^[A-Za-z0-9_]+(\\.[A-Za-z0-9_]+)*(\\|[a-z][a-z-]*(:[A-Za-z0-9_-]+)?)?$/';

    /**
     * Manual mapping of a binding: {record field: readPath}, invalid pairs dropped.
     *
     * @return array<string, string>
     */
    public static function map(mixed $map): array
    {
        $out = [];
        foreach (is_array($map) ? $map : [] as $field => $path) {
            if (is_string($field) && preg_match('/^[A-Za-z][A-Za-z0-9_]{0,62}$/', $field) === 1
                && is_string($path) && strlen($path) <= 200 && preg_match(self::MAP_PATH, $path) === 1) {
                $out[$field] = $path;
            }
        }
        ksort($out, SORT_STRING);
        return $out;
    }
}
