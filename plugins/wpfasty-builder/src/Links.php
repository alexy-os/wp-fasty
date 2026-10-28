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
 *   client   {open-now}: "Open now" / "Closed now" computed in the browser (finish() + footer()); "" without hours
 * Site data = the Codex settings contact block (@ui8kit/contract SettingsV04 contact, hours, hours_exceptions, map),
 * stored by the wpfasty-types plugin (WPFastyTypes\Contact::block()); options already loaded, no query.
 * index() lists every token with what it gives for the editor's picker (GET wpfasty-builder/v1/links): the editor
 * resolves tokens client-side from that one response.
 */
final class Links
{
    public const TOKEN =
        '/\{(home|posts|page|category|archive|site|tel|mailto|whatsapp|telegram|map|year|today|open-now)'
        . '(?::([a-z0-9_-]+))?\}/';
    /** {site:<key>} keys, in picker order */
    public const SITE = ['name', 'phone', 'email', 'address', 'hours'];
    /** contact tokens → the site data key each one is built from */
    public const CONTACT = [
        'tel' => 'phone', 'mailto' => 'email', 'whatsapp' => 'whatsapp', 'telegram' => 'telegram', 'map' => 'address',
    ];
    /** a Telegram username, with or without the @ */
    public const HANDLE = '/^@{0,1}([A-Za-z0-9_]{4,32})$/';
    /** map search when the site settings name no map provider */
    public const MAP = 'https://www.openstreetmap.org/search?query=';
    /** map links by provider (settings map.provider; unknown or none = osm): [pin at {lat},{lng}, search for {q}] */
    public const MAPS = [
        'osm' => ['https://www.openstreetmap.org/?mlat={lat}&mlon={lng}#map=17/{lat}/{lng}', self::MAP . '{q}'],
        'google' => [
            'https://www.google.com/maps/search/?api=1&query={lat}%2C{lng}',
            'https://www.google.com/maps/search/?api=1&query={q}',
        ],
        'yandex' => ['https://yandex.com/maps/?pt={lng},{lat}&z=17&l=map', 'https://yandex.com/maps/?text={q}'],
        'apple' => ['https://maps.apple.com/?ll={lat},{lng}&q={lat},{lng}', 'https://maps.apple.com/?q={q}'],
    ];
    /** what {open-now} resolves to when the site has hours: finish() turns it into the element the script fills */
    public const OPEN_NOW = "\u{E000}wpfb-open-now\u{E001}";
    public const WEEK = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    /** a slug a token can name (TOKEN's argument) */
    public const SLUG = '/^[a-z0-9_-]+$/';
    /** most pages / categories listed (the picker searches the list; a token still works for any slug) */
    public const MAX = 500;

    /** @var array<string, mixed>|null the contact block; null = read WPFastyTypes\Contact::block() */
    private static ?array $block = null;
    private static bool $footer = false;

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

    /** Site contact block (SettingsV04 contact, hours, hours_exceptions, map); tests and previews may set one. */
    public static function setBlock(?array $block): void
    {
        self::$block = $block;
        self::$cache = [];
    }

    /** @return array<string, mixed> */
    public static function block(): array
    {
        if (self::$block === null) {
            self::$block = class_exists(\WPFastyTypes\Contact::class) ? \WPFastyTypes\Contact::block() : [];
        }
        return self::$block;
    }

    /**
     * Site data of the {site:*} and contact tokens: name = SettingsV04 title (blogname), the rest from the settings
     * contact block: phone (E.164), email, address, hours (text of hoursText()), the Telegram / WhatsApp handles of
     * contact.links (WhatsApp falls back to the phone) and the map pin. "" for what the site does not have.
     *
     * @param array<string, mixed>|null $block block(); null = the site's
     * @return array{name: string, phone: string, email: string, address: string, hours: string, telegram: string,
     *   whatsapp: string, lat: string, lng: string, provider: string}
     */
    public static function data(?array $block = null): array
    {
        $block ??= self::block();
        $c = is_array($block['contact'] ?? null) ? $block['contact'] : [];
        $handle = static function (string $network) use ($c): string {
            foreach (is_array($c['links'] ?? null) ? $c['links'] : [] as $l) {
                if (is_array($l) && ($l['network'] ?? '') === $network && is_string($l['handle'] ?? null)) {
                    return $l['handle'];
                }
            }
            return '';
        };
        $map = is_array($block['map'] ?? null) ? $block['map'] : [];
        $coords = is_numeric($map['lat'] ?? null) && is_numeric($map['lng'] ?? null);
        $phone = is_string($c['phone'] ?? null) ? $c['phone'] : '';
        $whatsapp = $handle('whatsapp');
        return [
            'name' => html_entity_decode((string) get_option('blogname'), ENT_QUOTES, 'UTF-8'),
            'phone' => $phone,
            'email' => is_string($c['email'] ?? null) ? $c['email'] : '',
            'address' => is_string($c['address'] ?? null) ? $c['address'] : '',
            'hours' => self::hoursText(is_array($block['hours'] ?? null) ? $block['hours'] : []),
            'telegram' => $handle('telegram'),
            'whatsapp' => $whatsapp !== '' ? $whatsapp : $phone,
            'lat' => $coords ? (string) (float) $map['lat'] : '',
            'lng' => $coords ? (string) (float) $map['lng'] : '',
            'provider' => is_string($map['provider'] ?? null) ? $map['provider'] : '',
        ];
    }

    /**
     * Weekly hours as text: consecutive days with the same intervals grouped, closed days left out, e.g.
     * "Mon–Fri 09:00–18:00; Sat 10:00–14:00". Day names translatable; an interval belongs to the day it opens.
     *
     * @param list<mixed> $hours SettingsV04 hours
     */
    public static function hoursText(array $hours): string
    {
        $names = [
            'mon' => __('Mon', 'wpfasty-builder'), 'tue' => __('Tue', 'wpfasty-builder'),
            'wed' => __('Wed', 'wpfasty-builder'), 'thu' => __('Thu', 'wpfasty-builder'),
            'fri' => __('Fri', 'wpfasty-builder'), 'sat' => __('Sat', 'wpfasty-builder'),
            'sun' => __('Sun', 'wpfasty-builder'),
        ];
        $byDay = array_fill_keys(self::WEEK, []);
        foreach ($hours as $r) {
            if (!is_array($r) || !is_array($r['days'] ?? null)) {
                continue;
            }
            foreach ($r['days'] as $d) {
                if (isset($byDay[$d])) {
                    $byDay[$d][] = (string) ($r['open'] ?? '') . '–' . (string) ($r['close'] ?? '');
                }
            }
        }
        $groups = [];
        foreach (self::WEEK as $d) {
            $times = $byDay[$d];
            sort($times, SORT_STRING);
            $key = implode(', ', $times);
            $last = count($groups) - 1;
            if ($last >= 0 && $groups[$last][2] === $key && $key !== '') {
                $groups[$last][1] = $d;
                continue;
            }
            $groups[] = [$d, $d, $key];
        }
        $out = [];
        foreach ($groups as [$from, $to, $key]) {
            if ($key !== '') {
                $out[] = $names[$from] . ($from !== $to ? '–' . $names[$to] : '') . ' ' . $key;
            }
        }
        return implode('; ', $out);
    }

    /**
     * What a contact token links to, built from site data; "#" when the value it needs is empty. {map}: a pin at
     * lat/lng when the settings have one, else a search for the address; provider from the settings map (osm default).
     *
     * @param array<string, string> $data data()
     */
    public static function contact(string $kind, array $data): string
    {
        $v = trim((string) ($data[self::CONTACT[$kind] ?? ''] ?? ''));
        $digits = (string) preg_replace('/[^0-9]/', '', $v);
        $plus = str_starts_with($v, '+') ? '+' : '';
        $handle = preg_match(self::HANDLE, $v, $m) === 1 ? $m[1] : '';
        $lat = (string) ($data['lat'] ?? '');
        $lng = (string) ($data['lng'] ?? '');
        $pin = is_numeric($lat) && is_numeric($lng);
        $ok = match ($kind) {
            'tel', 'whatsapp' => $digits !== '',
            'mailto' => is_email($v) !== false,
            'telegram' => $handle !== '',
            'map' => $v !== '' || $pin,
            default => false,
        };
        if (!$ok) {
            return '#';
        }
        if ($kind === 'map') {
            $urls = self::MAPS[(string) ($data['provider'] ?? '')] ?? self::MAPS['osm'];
            return $pin
                ? str_replace(['{lat}', '{lng}'], [$lat, $lng], $urls[0])
                : str_replace('{q}', rawurlencode($v), $urls[1]);
        }
        return match ($kind) {
            'tel' => 'tel:' . $plus . $digits,
            'mailto' => 'mailto:' . $v,
            'whatsapp' => 'https://wa.me/' . $digits,
            default => 'https://t.me/' . $handle,
        };
    }

    /**
     * Block HTML after rendering: every {open-now} (OPEN_NOW) in text becomes an empty element the footer script
     * fills; inside a tag (an attribute) it is dropped. The page stays the same for every visitor (page caches keep
     * working): the state is computed in the browser from the hours in the footer.
     */
    public static function finish(string $html): string
    {
        if (!str_contains($html, self::OPEN_NOW)) {
            return $html;
        }
        $html = (string) preg_replace_callback(
            '/<[^>]*>|' . preg_quote(self::OPEN_NOW, '/') . '/u',
            static fn (array $m): string => $m[0] === self::OPEN_NOW
                ? '<span data-wpfb-open-now></span>'
                : str_replace(self::OPEN_NOW, '', $m[0]),
            $html,
        );
        if (!self::$footer && function_exists('add_action')) {
            self::$footer = true;
            add_action('wp_footer', [self::class, 'footer'], 20);
        }
        return $html;
    }

    /**
     * Data of the {open-now} script: hours, exceptions, the site time zone (IANA, else the UTC offset in minutes)
     * and the two labels; null without hours.
     *
     * @return array{tz: string, offset: int, hours: list<mixed>, exceptions: list<mixed>, open: string,
     *   closed: string}|null
     */
    public static function openNow(?array $block = null): ?array
    {
        $block ??= self::block();
        $hours = is_array($block['hours'] ?? null) ? array_values($block['hours']) : [];
        if ($hours === []) {
            return null;
        }
        return [
            'tz' => (string) get_option('timezone_string', ''),
            'offset' => (int) round((float) get_option('gmt_offset', 0) * 60),
            'hours' => $hours,
            'exceptions' => is_array($block['hours_exceptions'] ?? null)
                ? array_values($block['hours_exceptions']) : [],
            'open' => __('Open now', 'wpfasty-builder'),
            'closed' => __('Closed now', 'wpfasty-builder'),
        ];
    }

    /** The {open-now} script (wp_footer, only on pages that use the token and only with hours). */
    public static function footer(): void
    {
        $data = self::openNow();
        if ($data === null) {
            return;
        }
        $js = '(function(d){' . self::OPEN_NOW_JS . '})('
            . (string) wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . ');';
        if (function_exists('wp_print_inline_script_tag')) {
            wp_print_inline_script_tag($js, ['id' => 'wpfb-open-now']);
            return;
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- code + JSON encoded with JSON_HEX_TAG
        echo '<script id="wpfb-open-now">' . $js . '</script>';
    }

    /**
     * Client side of {open-now}: now in the site zone (Intl, else the fixed offset), intervals of today (the exception
     * of the date, else the weekly rules) and yesterday's intervals that run past midnight; refreshed every minute and
     * for elements added later (editor preview swaps).
     */
    public const OPEN_NOW_JS = 'var W=["mon","tue","wed","thu","fri","sat","sun"];'
        . 'function m(t){return +t.slice(0,2)*60+ +t.slice(3,5)}'
        . 'function now(){var t=new Date(),p;try{if(d.tz){p={};new Intl.DateTimeFormat("en-US",{timeZone:d.tz,'
        . 'year:"numeric",month:"2-digit",day:"2-digit",hour:"2-digit",minute:"2-digit",hourCycle:"h23"})'
        . '.formatToParts(t).forEach(function(x){p[x.type]=x.value});'
        . 'return{y:+p.year,mo:+p.month,da:+p.day,mi:(+p.hour%24)*60+ +p.minute}}}catch(e){}'
        . 't=new Date(t.getTime()+d.offset*6e4);'
        . 'return{y:t.getUTCFullYear(),mo:t.getUTCMonth()+1,da:t.getUTCDate(),mi:t.getUTCHours()*60+t.getUTCMinutes()}}'
        . 'function day(u){var t=new Date(u),s=t.toISOString().slice(0,10),w=W[(t.getUTCDay()+6)%7],r=[],x,i;'
        . 'for(i=0;i<d.exceptions.length;i++)if(d.exceptions[i].date===s)x=d.exceptions[i];'
        . 'if(x)return x.closed?[]:[[m(x.open),m(x.close)]];'
        . 'd.hours.forEach(function(h){if(h.days.indexOf(w)>=0)r.push([m(h.open),m(h.close)])});return r}'
        . 'function open(){var n=now(),u=Date.UTC(n.y,n.mo-1,n.da),k=n.mi;'
        . 'return day(u).some(function(i){return k>=i[0]&&k<(i[1]>i[0]?i[1]:1440)})'
        . '||day(u-864e5).some(function(i){return i[1]<i[0]&&k<i[1]})}'
        . 'function paint(){var o=open(),l=document.querySelectorAll("[data-wpfb-open-now]");'
        . 'for(var i=0;i<l.length;i++){if(l[i].textContent!==(o?d.open:d.closed))l[i].textContent=o?d.open:d.closed;'
        . 'l[i].setAttribute("data-state",o?"open":"closed")}}'
        . 'paint();setInterval(paint,6e4);'
        . 'if(window.MutationObserver)new MutationObserver(function(){'
        . 'if(document.querySelector("[data-wpfb-open-now]:not([data-state])"))paint()})'
        . '.observe(document.body,{childList:true,subtree:true});';

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
            'open-now' => self::openNow() !== null ? self::OPEN_NOW : '',
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
