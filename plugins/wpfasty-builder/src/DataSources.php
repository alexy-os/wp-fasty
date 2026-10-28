<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WP_Post;
use WP_Term;
use WPFastyBuilder\Codex\Compat;
use WPFastyBuilder\Codex\ReadPath;
use WPFastyBuilder\Codex\Sources as CodexSources;
use WPFastyTypes\Codex\Mapper;
use WPFastyTypes\Codex\Site;

/**
 * WordPress data for bound block paths, read as Codex entries. The sources are the kinds and taxonomies of the site's
 * Codex manifest (wpfasty-types `types/codex.manifest.json` + delivery); an item is the Codex entry of a post or term
 * (wpfasty-types Mapper) and a record field is `ReadPath::read(entry, path)` where the path is the catalog's own when
 * the source has it (exact), the R1–R9 rewrite when Compat finds one (auto), the binding's `map` (manual), or the
 * presentational default (Q6). Nothing here knows a post type, taxonomy or meta key.
 *
 * WP specifics kept here (the adapter side): `slug|route` is the permalink, a purchasable kind's price is the
 * effective price (sale price when it is lower) with badge "sale", posts are ordered by date, other kinds by
 * menu order.
 */
final class DataSources
{
    public const MAX = 24;
    /** the readPath of the current record's body: the_content (DataSources::content), the only unfiltered markup */
    public const CONTENT = 'content|html';
    private const TYPES = ['string' => 'string', 'number' => 'number', 'boolean' => 'boolean'];

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $models = null;

    public static function site(): ?Site
    {
        return class_exists(Site::class) ? Site::current() : null;
    }

    /** @return array<string, array<string, mixed>> Codex source models (Codex\Sources::fromManifest) */
    public static function models(): array
    {
        if (self::$models === null) {
            $site = self::site();
            self::$models = $site !== null ? CodexSources::fromManifest($site->manifest, $site->delivery) : [];
        }
        return self::$models;
    }

    public static function reset(): void
    {
        self::$models = null;
    }

    /**
     * The sources available on this site; with a catalog, `kinds` lists the data kinds each one fills exactly or by
     * an auto rule (what the editor offers) and `levels` their level.
     *
     * @return array<string, array{id: string, label: string, kinds: list<string>, levels: array<string, string>,
     *   type: string, object: string, purchasable: bool}>
     */
    public static function all(?Catalog $catalog = null): array
    {
        $out = [];
        $slots = $catalog?->slotsByKind() ?? [];
        foreach (self::models() as $id => $m) {
            $levels = [];
            foreach ($slots as $kind => $slot) {
                $lv = self::level($slot, $m);
                if ($lv !== Compat::MANUAL) {
                    $levels[$kind] = $lv;
                }
            }
            $out[$id] = [
                'id' => $id,
                'label' => self::label($m),
                'kinds' => array_keys($levels),
                'levels' => $levels,
                'type' => $m['term'] ? 'taxonomy' : 'post_type',
                'object' => $m['term'] ? (string) $m['taxonomy'] : (string) $m['post_type'],
                'purchasable' => $m['purchasable'] !== null,
            ];
        }
        return (array) apply_filters('wpfasty_builder_data_sources', $out);
    }

    /** @param array<string, mixed> $m */
    private static function label(array $m): string
    {
        if ($m['term']) {
            $t = get_taxonomy((string) $m['taxonomy']);
            return $t ? html_entity_decode((string) $t->labels->name, ENT_QUOTES, 'UTF-8') : (string) $m['label'];
        }
        $o = get_post_type_object((string) $m['post_type']);
        return $o ? html_entity_decode((string) $o->labels->name, ENT_QUOTES, 'UTF-8') : (string) $m['label'];
    }

    /**
     * Level of a catalog slot for a source: required = the record fields that are not optional.
     *
     * @param array<string, mixed> $slot Catalog::slot()
     * @param array<string, mixed> $m
     */
    public static function level(array $slot, array $m): string
    {
        [$required, $needs] = self::required($slot);
        return Compat::slot($required, $m, $needs)['level'];
    }

    /**
     * @param array<string, mixed> $slot
     * @return array{0: list<string>, 1: array<string, array{0: string, 1: list<string>}>}
     */
    private static function required(array $slot): array
    {
        $required = [];
        $needs = [];
        foreach ($slot['defs'] as $def) {
            $path = $slot['fields'][(string) ($def['name'] ?? '')] ?? null;
            if ($path === null || !empty($def['optional'])) {
                continue;
            }
            $required[] = $path;
            // a string record field takes any scalar (record() casts it: year 2024 → "2024")
            $type = self::TYPES[(string) ($def['type'] ?? '')] ?? null;
            if ($type !== null && $type !== 'string') {
                $needs[$path] = [$type, []];
            }
        }
        return [$required, $needs];
    }

    /**
     * Records for one binding.
     *
     * @param array{source: string, limit?: int, term?: string, sale?: bool, map?: array<string, string>} $binding
     * @param array<string, mixed>|null $slot Catalog::slot() of the bound path
     * @return list<array<string, mixed>>
     */
    public static function resolve(array $binding, string $kind, ?array $slot = null): array
    {
        return self::run($binding, $slot, null)[0];
    }

    /**
     * Records of one page of a binding and the number of pages (a list block with a pager, Pager): the binding's
     * `limit` per page, page $page of the same query (paged, found rows of that one query: no query per item, no
     * second list query). A term source has one page.
     *
     * @param array{source: string, limit?: int, term?: string, sale?: bool, map?: array<string, string>} $binding
     * @param array<string, mixed>|null $slot
     * @return array{0: list<array<string, mixed>>, 1: int}
     */
    public static function paged(array $binding, string $kind, ?array $slot, int $page): array
    {
        return self::run($binding, $slot, max(1, $page));
    }

    /**
     * @param array<string, mixed>      $binding
     * @param array<string, mixed>|null $slot
     * @return array{0: list<array<string, mixed>>, 1: int} records, pages
     */
    private static function run(array $binding, ?array $slot, ?int $page): array
    {
        $m = self::models()[$binding['source']] ?? null;
        $site = self::site();
        if ($m === null || $site === null || $slot === null) {
            return [[], 1];
        }
        $mapper = new Mapper($site);
        $limit = max(1, min(self::MAX, (int) ($binding['limit'] ?? 6)));
        [$entries, $pages] = $m['term']
            ? [self::terms($m, $limit, $mapper), 1]
            : self::posts($m, $binding, $limit, $mapper, $site, $page);
        $plan = self::plan($slot, $m, (array) ($binding['map'] ?? []));
        $opts = self::opts($plan, $entries, $mapper, $site);
        $out = [];
        foreach ($entries as $e) {
            $out[] = self::record(self::project($e, $m), $plan, $opts, $m);
        }
        return [$out, $pages];
    }

    /**
     * The record of the current route for a current-scope slot (codegen-x `scope: "current"`; the richtext block):
     * the queried post (or $post, e.g. the page the editor previews) as its Codex entry, read by the slot's fields.
     * WP specifics kept here: `content` is the body after the `the_content` filters (blocks, shortcodes, embeds,
     * wpautop; the password form for a protected post), the platform-neutral record only says it is trusted markup
     * (`content|html`). Memoized per post and slot: any number of blocks on a page costs one read.
     *
     * @param array<string, mixed> $slot Catalog::currentSlot()
     * @return array<string, mixed> the record; [] without a post of a kind of the site's Codex manifest
     */
    public static function current(array $slot, ?WP_Post $post = null): array
    {
        static $memo = [];
        $post ??= get_queried_object() instanceof WP_Post ? get_queried_object() : null;
        $site = self::site();
        if (!$post instanceof WP_Post || $site === null) {
            return [];
        }
        $m = null;
        foreach (self::models() as $model) {
            if (!$model['term'] && (string) $model['post_type'] === $post->post_type) {
                $m = $model;
                break;
            }
        }
        if ($m === null) {
            return [];
        }
        $key = $post->ID . '|' . md5((string) wp_json_encode([$slot['fields'] ?? [], $slot['defs'] ?? []]));
        if (isset($memo[$key])) {
            return $memo[$key];
        }
        $mapper = new Mapper($site);
        $e = $mapper->entry($post, (string) $m['kind']);
        $e['content'] = self::content($post);
        $plan = self::plan($slot, $m);
        $memo[$key] = self::record(self::project($e, $m), $plan, self::opts($plan, [$e], $mapper, $site), $m, true);
        return $memo[$key];
    }

    /** the body of a post as the theme prints it: get_the_content() through the `the_content` filters */
    private static function content(WP_Post $post): string
    {
        $prev = $GLOBALS['post'] ?? null;
        // the_content filters (blocks, shortcodes) read the global post: set for the call, restored after it
        $GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        setup_postdata($post);
        $html = (string) apply_filters('the_content', get_the_content(null, false, $post));
        $html = str_replace(']]>', ']]&gt;', $html);
        $GLOBALS['post'] = $prev; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        if ($prev instanceof WP_Post) {
            setup_postdata($prev);
        }
        return $html;
    }

    /**
     * Record field → [path|null, default, type]: the effective readPath of every record field.
     *
     * @param array<string, mixed>  $slot
     * @param array<string, mixed>  $m
     * @param array<string, string> $map manual mapping of the binding
     * @return array<string, array{0: ?string, 1: mixed, 2: string}>
     */
    public static function plan(array $slot, array $m, array $map = []): array
    {
        $plan = [];
        foreach ($slot['defs'] as $def) {
            $name = (string) ($def['name'] ?? '');
            $type = (string) ($def['type'] ?? 'string');
            $path = $slot['fields'][$name] ?? null;
            if (isset($map[$name])) {
                $plan[$name] = [(string) $map[$name], null, $type];
                continue;
            }
            if ($path === null) {
                continue;
            }
            $need = isset(self::TYPES[$type]) && $type !== 'string' ? [self::TYPES[$type], []] : null;
            $r = Compat::resolve($path, $m, $need);
            $plan[$name] = [$r['path'], $r['default'] ?? null, $type];
        }
        return $plan;
    }

    /**
     * @param array<string, array{0: ?string, 1: mixed, 2: string}> $plan
     * @param array<string, mixed> $e
     * @param array<string, mixed> $opts
     * @param array<string, mixed> $m
     * @return array<string, mixed>
     */
    private static function record(array $e, array $plan, array $opts, array $m, bool $current = false): array
    {
        $rec = [];
        foreach ($plan as $name => [$path, $default, $type]) {
            if ($type === 'TrustedHtml') {
                $v = $path !== null ? ReadPath::read($e, $path, $opts) : null;
                $rec[$name] = self::trusted($v, $current && $path === self::CONTENT);
                continue;
            }
            if ($path === 'slug|route') {
                $wp = Mapper::wpId((string) $m['kind'], (string) $e['id']);
                $link = $m['term'] ? get_term_link($wp, (string) $m['taxonomy']) : get_permalink($wp);
                $v = is_string($link) ? $link : null;
            } else {
                $v = $path !== null ? ReadPath::read($e, $path, $opts) : null;
            }
            $rec[$name] = self::cast($v ?? $default, $type);
        }
        return $rec;
    }

    /**
     * A TrustedHtml value as the templates may print it unescaped (og_html): only the {"__html": string} marker of the
     * `|html` pipe. The body of the current record (`content|html` = the_content after its filters: post_content was
     * filtered by kses on save for users without unfiltered_html, like the theme's single view) passes as it is;
     * any other markup (a meta or settings field read with `|html`, e.g. metadata.aside.value, never filtered on save)
     * goes through wp_kses_post — the same tags an author may post. Anything else is null, and so is markup that is
     * blank (PHP trim()) after kses: the `|html` rule of codegen-x / ReadPath (blank = absent), so a part shown only
     * when its markup is set (the RichText side note) stays out.
     *
     * @return array{__html: string}|null
     */
    public static function trusted(mixed $v, bool $theContent): ?array
    {
        if (!is_array($v) || !is_string($v['__html'] ?? null)) {
            return null;
        }
        $html = $theContent ? $v['__html'] : self::kses($v['__html']);
        return trim($html) === '' ? null : ['__html' => $html];
    }

    /** wp_kses_post; without WordPress (offline tools) the text, escaped */
    private static function kses(string $html): string
    {
        return function_exists('wp_kses_post')
            ? (string) wp_kses_post($html)
            : htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** A value in the record's type; absent → the type's empty value (templates read every declared field). */
    private static function cast(mixed $v, string $type): mixed
    {
        return match ($type) {
            'number' => is_int($v) || is_float($v) ? $v : 0,
            'boolean' => is_bool($v) ? $v : false,
            'string' => is_scalar($v) && !is_bool($v) ? (string) $v : '',
            'string[]' => is_array($v) ? array_values(array_map('strval', $v)) : [],
            // trusted markup: only the {"__html": string} marker of the `|html` pipe; anything else is not markup
            'TrustedHtml' => self::trusted($v, false),
            // a type that is not TrustedHtml never carries the marker (a manual mapping `…|html` stays text)
            default => is_array($v) ? Layout::untrust(['v' => $v])['v'] : $v,
        };
    }

    /**
     * Purchasable kinds: the price a buyer pays (sale price when set and lower), badge "sale" unless set.
     *
     * @param array<string, mixed> $e
     * @param array<string, mixed> $m
     * @return array<string, mixed>
     */
    private static function project(array $e, array $m): array
    {
        $p = $m['purchasable'];
        if ($p === null) {
            return $e;
        }
        $price = $e['metadata'][$p['price']]['value'] ?? null;
        $sale = isset($p['sale']) ? ($e['metadata'][$p['sale']]['value'] ?? null) : null;
        if (is_int($price) && is_int($sale) && $sale > 0 && $sale < $price) {
            $e['metadata'][$p['price']] = ['value' => $sale];
            $e['metadata']['badge'] ??= ['value' => 'sale'];
        }
        return $e;
    }

    /**
     * @param array<string, mixed> $m
     * @return list<array<string, mixed>>
     */
    private static function terms(array $m, int $limit, Mapper $mapper): array
    {
        $terms = get_terms([
            'taxonomy' => (string) $m['taxonomy'],
            'hide_empty' => true,
            'number' => $limit,
            'orderby' => 'count',
            'order' => 'DESC',
        ]);
        $out = [];
        foreach (is_array($terms) ? $terms : [] as $t) {
            if ($t instanceof WP_Term) {
                $out[] = $mapper->term($t, true);
            }
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $m
     * @param array<string, mixed> $binding
     * @param integer|null         $page    a page of the list (with its number of pages), null: the first $limit
     * @return array{0: list<array<string, mixed>>, 1: int} entries, pages
     */
    private static function posts(
        array $m,
        array $binding,
        int $limit,
        Mapper $mapper,
        Site $site,
        ?int $page = null,
    ): array {
        $kind = (string) $m['kind'];
        $args = [
            'post_type' => (string) $m['post_type'],
            'post_status' => 'publish',
            // as the Codex REST (wpfasty-types): a password-protected post is not listed (its body, excerpt and
            // fields are behind the password)
            'has_password' => false,
            'posts_per_page' => $limit,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'orderby' => $kind === 'post' ? 'date' : ['menu_order' => 'ASC', 'date' => 'DESC'],
        ];
        if ($kind === 'page') {
            $args['post_parent'] = 0;
        }
        $term = (string) ($binding['term'] ?? '');
        $tax = $site->roleTaxonomy($kind, 'category');
        if ($term !== '' && $tax !== '') {
            $args['tax_query'] = [['taxonomy' => $tax, 'field' => 'slug', 'terms' => $term]];
        }
        $p = $m['purchasable'];
        $saleKey = $p !== null && isset($p['sale']) ? ($site->wp($kind)['meta'][$p['sale']] ?? null) : null;
        if (!empty($binding['sale']) && is_string($saleKey)) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small demo catalogs, bounded by limit.
            $args['meta_query'] = [['key' => $saleKey, 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC']];
        }
        $pages = 1;
        if ($page === null) {
            $posts = get_posts($args);
        } else {
            // one query with its found rows (get_posts() drops them): the page and the number of pages
            $q = new \WP_Query(
                array_merge($args, ['paged' => $page, 'no_found_rows' => false, 'suppress_filters' => true]),
            );
            $posts = $q->posts;
            $pages = max(1, (int) $q->max_num_pages);
        }
        $out = [];
        foreach ($posts as $post) {
            if ($post instanceof WP_Post) {
                $out[] = $mapper->entry($post, $kind);
            }
        }
        return [$out, $pages];
    }

    /**
     * readPath options the plan needs: media of the featured images, authors, referenced terms, settings, locale.
     * Loaded in one query each (no per-item queries).
     *
     * @param array<string, array{0: ?string, 1: mixed, 2: string}> $plan
     * @param list<array<string, mixed>> $entries
     * @return array<string, mixed>
     */
    private static function opts(array $plan, array $entries, Mapper $mapper, Site $site): array
    {
        $pipes = implode(' ', array_map(static fn ($p): string => (string) $p[0], $plan));
        $opts = ['locale' => Mapper::locale(), 'now' => time()];
        if (str_contains($pipes, '|media')) {
            $ids = [];
            foreach ($entries as $e) {
                if (is_string($e['featured_media_id'] ?? null)) {
                    $ids[] = Mapper::wpId('media', $e['featured_media_id']);
                }
            }
            $opts['media'] = [];
            $ids = array_values(array_filter(array_unique($ids)));
            if ($ids !== []) {
                $atts = get_posts([
                    'post_type' => 'attachment', 'post_status' => 'inherit', 'post__in' => $ids,
                    'posts_per_page' => count($ids), 'no_found_rows' => true,
                ]);
                foreach ($atts as $a) {
                    if ($a instanceof WP_Post) {
                        $opts['media'][] = $mapper->media($a);
                    }
                }
            }
        }
        if (str_contains($pipes, '|author')) {
            $ids = [];
            foreach ($entries as $e) {
                if (is_string($e['author_id'] ?? null)) {
                    $ids[] = Mapper::wpId('user', $e['author_id']);
                }
            }
            $ids = array_values(array_filter(array_unique($ids)));
            $opts['users'] = $ids === [] ? [] : array_map([$mapper, 'user'], get_users(['include' => $ids]));
        }
        if (preg_match('/\|term(-slug|-names)?:/', $pipes) === 1) {
            $opts['terms'] = [];
            foreach ($entries as $e) {
                foreach ((array) ($e['terms'] ?? []) as $ref) {
                    $tax = (string) $ref['taxonomy'];
                    $t = get_term(Mapper::wpId($tax, (string) $ref['term_id']), $tax);
                    if ($t instanceof WP_Term) {
                        $opts['terms'][$t->term_taxonomy_id] = $mapper->term($t, true); // cached by get_the_terms
                    }
                }
            }
            $opts['terms'] = array_values($opts['terms']);
        }
        if (str_contains($pipes, '|settings:')) {
            $opts['settings'] = $mapper->settings();
        }
        return $opts;
    }
}
