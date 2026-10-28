<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Tokens in props, resolved in every string prop when a block renders, so a layout survives a domain change or a
 * Playground import:
 *   links    {home}, {posts} (the posts page, or home), {page:<slug>}, {category:<slug>}, {archive:<post type>};
 *            a token may be followed by a path or a query: "{page:about}#team"
 *   site     {site:name|phone|email|address|hours}: the site data of data(); "" when the site has no such value
 *   contact  {tel}, {mailto}, {whatsapp}, {telegram}, {map}: links built from the site data; "#" without it
 *   date     {year}, {today} (date_format), in the site time zone
 * index() lists every token with what it gives for the editor's picker (GET wpfasty-builder/v1/links): the editor
 * resolves tokens client-side from that one response.
 */
final class Links
{
    public const TOKEN =
        '/\{(home|posts|page|category|archive|site|tel|mailto|whatsapp|telegram|map|year|today)(?::([a-z0-9_-]+))?\}/';
    /** {site:<key>} keys, in picker order */
    public const SITE = ['name', 'phone', 'email', 'address', 'hours'];
    /** contact tokens → the site data key each one is built from */
    public const CONTACT = [
        'tel' => 'phone', 'mailto' => 'email', 'whatsapp' => 'phone', 'telegram' => 'telegram', 'map' => 'address',
    ];
    /** a Telegram username, with or without the @ */
    public const HANDLE = '/^@{0,1}([A-Za-z0-9_]{4,32})$/';
    /** map search when the site settings name no map provider */
    public const MAP = 'https://www.openstreetmap.org/search?query=';
    /** a slug a token can name (TOKEN's argument) */
    public const SLUG = '/^[a-z0-9_-]+$/';
    /** most pages / categories listed (the picker searches the list; a token still works for any slug) */
    public const MAX = 500;

    /** @var array<string, string> */
    private static array $cache = [];

    public static function resolve(string $value): string
    {
        if (!str_contains($value, '{')) {
            return $value;
        }
        return (string) preg_replace_callback(self::TOKEN, static function (array $m): string {
            $key = $m[0];
            return self::$cache[$key] ??= self::value($m[1], $m[2] ?? '');
        }, $value);
    }

    /**
     * Site data of the {site:*} and contact tokens, only from what the site already has: the Codex settings
     * (SettingsV04 title → name). Phone, email, address, hours and the Telegram handle have no field in the settings
     * contract (ui8kit/contract a0ae505), the Customizer or the wpfasty-types options yet: "" until they get one.
     *
     * @return array{name: string, phone: string, email: string, address: string, hours: string, telegram: string}
     */
    public static function data(): array
    {
        return [
            'name' => html_entity_decode((string) get_option('blogname'), ENT_QUOTES, 'UTF-8'),
            'phone' => '',
            'email' => '',
            'address' => '',
            'hours' => '',
            'telegram' => '',
        ];
    }

    /**
     * What a contact token links to, built from site data; "#" when the value it needs is empty.
     *
     * @param array<string, string> $data data()
     */
    public static function contact(string $kind, array $data): string
    {
        $v = trim((string) ($data[self::CONTACT[$kind] ?? ''] ?? ''));
        $digits = (string) preg_replace('/[^0-9]/', '', $v);
        $plus = str_starts_with($v, '+') ? '+' : '';
        $handle = preg_match(self::HANDLE, $v, $m) === 1 ? $m[1] : '';
        $ok = match ($kind) {
            'tel', 'whatsapp' => $digits !== '',
            'mailto' => is_email($v) !== false,
            'telegram' => $handle !== '',
            'map' => $v !== '',
            default => false,
        };
        if (!$ok) {
            return '#';
        }
        return match ($kind) {
            'tel' => 'tel:' . $plus . $digits,
            'mailto' => 'mailto:' . $v,
            'whatsapp' => 'https://wa.me/' . $digits,
            'telegram' => 'https://t.me/' . $handle,
            default => self::MAP . rawurlencode($v),
        };
    }

    /** {year} / {today} now in the site time zone ({today} in the date_format setting) */
    public static function date(string $kind): string
    {
        $format = $kind === 'year' ? 'Y' : (string) get_option('date_format', 'F j, Y');
        return (string) wp_date($format !== '' ? $format : 'F j, Y');
    }

    private static function value(string $kind, string $arg): string
    {
        return match ($kind) {
            'site' => in_array($arg, self::SITE, true) ? self::data()[$arg] : '',
            'tel', 'mailto', 'whatsapp', 'telegram', 'map' => self::contact($kind, self::data()),
            'year', 'today' => self::date($kind),
            default => self::url($kind, $arg),
        };
    }

    /**
     * @param array<mixed> $props
     * @return array<mixed>
     */
    public static function walk(array $props): array
    {
        foreach ($props as $k => $v) {
            if (is_array($v)) {
                $props[$k] = self::walk($v);
            } elseif (is_string($v)) {
                $props[$k] = self::resolve($v);
            }
        }
        return $props;
    }

    /**
     * The link targets of the site in one response, with the URL each token resolves to: site ({home}, {posts}),
     * published top-level pages (what {page:<slug>} finds), categories and the public post type archives. Four
     * queries whatever the number of items (pages, categories; no meta or term caches primed). contact (actions, for
     * link fields) and data (site data and dates, for text fields) come from options already loaded: no query.
     *
     * @return array{
     *   home: string,
     *   site: list<array{token: string, label: string, url: string}>,
     *   pages: list<array{token: string, label: string, slug: string, url: string}>,
     *   sections: list<array{token: string, label: string, slug: string, kind: string, url: string}>,
     *   contact: list<array{token: string, label: string, url: string, source: string}>,
     *   data: list<array{token: string, label: string, value: string}>
     * }
     */
    public static function index(): array
    {
        $home = home_url('/');
        $postsPage = (int) get_option('page_for_posts');
        $site = [
            ['token' => '{home}', 'label' => __('Home', 'wpfasty-builder'), 'url' => $home],
            [
                'token' => '{posts}',
                'label' => __('Posts', 'wpfasty-builder'),
                'url' => $postsPage > 0 ? self::str(get_page_link($postsPage), $home) : $home,
            ],
        ];
        $pages = [];
        $found = get_posts([
            'post_type' => 'page', 'post_status' => 'publish', 'post_parent' => 0, 'numberposts' => self::MAX,
            'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true,
            'update_post_meta_cache' => false, 'update_post_term_cache' => false,
        ]);
        foreach (is_array($found) ? $found : [] as $p) {
            if (!$p instanceof \WP_Post || preg_match(self::SLUG, (string) $p->post_name) !== 1) {
                continue;
            }
            $slug = (string) $p->post_name;
            $title = trim(wp_strip_all_tags((string) $p->post_title));
            $pages[] = [
                'token' => '{page:' . $slug . '}',
                'label' => $title !== '' ? $title : $slug,
                'slug' => $slug,
                'url' => self::str(get_page_link($p), $home),
            ];
        }
        $sections = [];
        $terms = get_terms([
            'taxonomy' => 'category', 'hide_empty' => false, 'number' => self::MAX, 'orderby' => 'name',
            'update_term_meta_cache' => false,
        ]);
        foreach (is_array($terms) ? $terms : [] as $t) {
            if (!$t instanceof \WP_Term || preg_match(self::SLUG, (string) $t->slug) !== 1) {
                continue;
            }
            $sections[] = [
                'token' => '{category:' . $t->slug . '}',
                'label' => (string) $t->name,
                'slug' => (string) $t->slug,
                'kind' => 'category',
                'url' => self::str(get_category_link($t), $home),
            ];
        }
        foreach (get_post_types(['public' => true, 'has_archive' => true], 'objects') as $name => $type) {
            $name = (string) $name;
            if (preg_match(self::SLUG, $name) !== 1) {
                continue;
            }
            $label = is_object($type) && isset($type->labels->name) ? (string) $type->labels->name : $name;
            $sections[] = [
                'token' => '{archive:' . $name . '}',
                'label' => $label,
                'slug' => $name,
                'kind' => 'archive',
                'url' => self::str(get_post_type_archive_link($name), $home),
            ];
        }
        $data = self::data();
        $contactLabels = [
            'tel' => __('Call', 'wpfasty-builder'), 'mailto' => __('Email', 'wpfasty-builder'),
            'whatsapp' => __('WhatsApp', 'wpfasty-builder'), 'telegram' => __('Telegram', 'wpfasty-builder'),
            'map' => __('Map', 'wpfasty-builder'),
        ];
        $contact = [];
        foreach (self::CONTACT as $kind => $source) {
            $contact[] = [
                'token' => '{' . $kind . '}', 'label' => $contactLabels[$kind],
                'url' => self::contact($kind, $data), 'source' => $source,
            ];
        }
        $dataLabels = [
            'name' => __('Site name', 'wpfasty-builder'), 'phone' => __('Phone', 'wpfasty-builder'),
            'email' => __('Email', 'wpfasty-builder'), 'address' => __('Address', 'wpfasty-builder'),
            'hours' => __('Opening hours', 'wpfasty-builder'),
        ];
        $values = [];
        foreach (self::SITE as $key) {
            $values[] = ['token' => '{site:' . $key . '}', 'label' => $dataLabels[$key], 'value' => $data[$key]];
        }
        $values[] = ['token' => '{year}', 'label' => __('Year', 'wpfasty-builder'), 'value' => self::date('year')];
        $values[] = ['token' => '{today}', 'label' => __('Today', 'wpfasty-builder'), 'value' => self::date('today')];
        return [
            'home' => $home, 'site' => $site, 'pages' => $pages, 'sections' => $sections,
            'contact' => $contact, 'data' => $values,
        ];
    }

    private static function str(mixed $url, string $fallback): string
    {
        return is_string($url) && $url !== '' ? $url : $fallback;
    }

    private static function url(string $kind, string $arg): string
    {
        $home = home_url('/');
        $url = match ($kind) {
            'home' => $home,
            'posts' => (int) get_option('page_for_posts') > 0
                ? get_permalink((int) get_option('page_for_posts'))
                : $home,
            'page' => ($p = get_page_by_path($arg)) instanceof \WP_Post ? get_permalink($p) : $home,
            'category' => ($t = get_category_by_slug($arg)) instanceof \WP_Term ? get_category_link($t) : $home,
            'archive' => post_type_exists($arg) ? get_post_type_archive_link($arg) : $home,
            default => $home,
        };
        return is_string($url) && $url !== '' ? $url : $home;
    }
}
