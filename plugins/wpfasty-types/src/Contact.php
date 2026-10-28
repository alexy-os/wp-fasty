<?php

declare(strict_types=1);

namespace WPFastyTypes;

/**
 * Site contact data of the Codex settings contact block (@ui8kit/contract SettingsV04 `contact`, `hours`,
 * `hours_exceptions`, `map`; the contract adapter maps the same options, WP_CONTACT_OPTIONS). One option per core
 * field, every one registered with show_in_rest (/wp/v2/settings), edited on Settings → Contact & hours.
 *
 * Stored values are the core values: phone E.164, email, address (the site locale's one), links [{network, handle}]
 * in the order given, hours [{days, open, close}], exceptions [{date, closed: true} | {date, open, close}],
 * map {provider?, lat?, lng?}. "" / [] = not set. The form edits lists as text lines; the sanitizers accept both the
 * text and the REST arrays and drop what the contract would reject.
 */
final class Contact
{
    public const PAGE = 'wpfasty-contact';
    public const GROUP = 'wpfasty_contact';
    /** option → core settings path (contract WP_CONTACT_OPTIONS) */
    public const OPTIONS = [
        'wpfasty_contact_phone' => 'contact.phone',
        'wpfasty_contact_email' => 'contact.email',
        'wpfasty_contact_address' => 'contact.address',
        'wpfasty_contact_links' => 'contact.links',
        'wpfasty_hours' => 'hours',
        'wpfasty_hours_exceptions' => 'hours_exceptions',
        'wpfasty_map' => 'map',
    ];
    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
    /** map providers the builder links to (the contract allows any identifier) */
    public const PROVIDERS = ['osm', 'google', 'yandex', 'apple'];
    public const E164 = '/^\+[1-9][0-9]{1,14}$/';
    public const EMAIL = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
    public const IDENT = '/^[a-z][a-z0-9_-]{0,62}$/';
    public const TIME = '/^([01][0-9]|2[0-3]):[0-5][0-9]$/';
    public const CLOSE = '/^(([01][0-9]|2[0-3]):[0-5][0-9]|24:00)$/';

    public static function boot(): void
    {
        add_action('init', [self::class, 'register']);
        if (is_admin()) {
            add_action('admin_menu', [self::class, 'menu']);
            add_action('admin_init', [self::class, 'fields']);
        }
    }

    public static function register(): void
    {
        $time = ['type' => 'string', 'pattern' => '^[0-9]{2}:[0-9]{2}$'];
        $link = [
            'type' => 'object', 'additionalProperties' => false,
            'properties' => ['network' => ['type' => 'string'], 'handle' => ['type' => 'string']],
        ];
        $rule = [
            'type' => 'object', 'additionalProperties' => false,
            'properties' => [
                'days' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => self::DAYS]],
                'open' => $time, 'close' => $time,
            ],
        ];
        $exception = [
            'type' => 'object', 'additionalProperties' => false,
            'properties' => [
                'date' => ['type' => 'string', 'pattern' => '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'],
                'closed' => ['type' => 'boolean'], 'open' => $time, 'close' => $time,
            ],
        ];
        $map = [
            'type' => 'object', 'additionalProperties' => false,
            'properties' => [
                'provider' => ['type' => 'string'], 'lat' => ['type' => 'number'], 'lng' => ['type' => 'number'],
            ],
        ];
        $defs = [
            'wpfasty_contact_phone' => [
                'string', '', [], 'phone', __('Site phone, E.164 (+12025550143).', 'wpfasty-types'),
            ],
            'wpfasty_contact_email' => ['string', '', [], 'email', __('Site contact email.', 'wpfasty-types')],
            'wpfasty_contact_address' => ['string', '', [], 'address', __('Site postal address.', 'wpfasty-types')],
            'wpfasty_contact_links' => [
                'array', [], ['items' => $link], 'links', __('Messengers and social profiles.', 'wpfasty-types'),
            ],
            'wpfasty_hours' => ['array', [], ['items' => $rule], 'hours', __('Weekly opening hours.', 'wpfasty-types')],
            'wpfasty_hours_exceptions' => [
                'array', [], ['items' => $exception], 'exceptions', __('Opening hours exceptions.', 'wpfasty-types'),
            ],
            'wpfasty_map' => ['object', [], $map, 'map', __('Map pin: provider and coordinates.', 'wpfasty-types')],
        ];
        foreach ($defs as $option => [$type, $default, $schema, $sanitize, $description]) {
            register_setting(self::GROUP, $option, [
                'type' => $type,
                'default' => $default,
                'show_in_rest' => ['schema' => ['type' => $type] + $schema],
                'sanitize_callback' => [self::class, 'save' . ucfirst($sanitize)],
                'description' => $description,
            ]);
        }
    }

    public static function phone(mixed $v): string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        $s = (string) preg_replace('/[\s().\-\x{2010}-\x{2015}]/u', '', $s);
        if (str_starts_with($s, '00')) {
            $s = '+' . substr($s, 2);
        }
        return preg_match(self::E164, $s) === 1 ? $s : '';
    }

    public static function email(mixed $v): string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        return preg_match(self::EMAIL, $s) === 1 && (!function_exists('is_email') || is_email($s) !== false) ? $s : '';
    }

    /** one line, no tags or control characters */
    public static function line(mixed $v): string
    {
        $s = is_scalar($v) ? strip_tags((string) $v) : '';
        return trim((string) preg_replace('/[\s\x{0}-\x{1F}\x{7F}]+/u', ' ', $s));
    }

    /** a handle in the network's own form: Telegram without "@" or t.me/, WhatsApp as E.164, others as given */
    public static function handle(string $network, mixed $v): string
    {
        $h = is_scalar($v) ? trim((string) $v) : '';
        if ($network === 'telegram') {
            $h = (string) preg_replace('~^(https?://)?(t\.me|telegram\.me)/~i', '', ltrim($h, '@'));
        } elseif ($network === 'whatsapp') {
            $h = (string) preg_replace('~^(https?://)?wa\.me/~i', '', $h);
            $h = self::phone(str_starts_with($h, '+') || str_starts_with($h, '00') ? $h : '+' . $h);
        }
        return preg_match('/^\S+$/u', $h) === 1 ? $h : '';
    }

    /**
     * @return list<array{network: string, handle: string}>
     */
    public static function links(mixed $v, ?array &$bad = null): array
    {
        $bad = [];
        $out = [];
        foreach (self::rows($v) as $n => $row) {
            if (is_string($row)) {
                $parts = preg_split('/\s+/', trim($row), 2) ?: [];
                $row = ['network' => $parts[0] ?? '', 'handle' => $parts[1] ?? ''];
            }
            $network = strtolower(trim((string) (is_array($row) ? ($row['network'] ?? '') : '')));
            $handle = self::handle($network, is_array($row) ? ($row['handle'] ?? '') : '');
            if (preg_match(self::IDENT, $network) !== 1 || $handle === '') {
                $bad[] = $n + 1;
                continue;
            }
            $out[] = ['network' => $network, 'handle' => $handle];
        }
        return $out;
    }

    /**
     * Text lines "mon-fri 09:00-18:00", "sat,sun 10:00-14:00" or REST rules.
     *
     * @return list<array{days: list<string>, open: string, close: string}>
     */
    public static function hours(mixed $v, ?array &$bad = null): array
    {
        $bad = [];
        $out = [];
        foreach (self::rows($v) as $n => $row) {
            if (is_string($row)) {
                $row = preg_match('/^\s*(\S+)\s+(\S+?)\s*[-\x{2013}\x{2014}]\s*(\S+)\s*$/u', $row, $m) === 1
                    ? ['days' => self::days($m[1]), 'open' => self::time($m[2]), 'close' => self::time($m[3])]
                    : [];
            }
            $days = is_array($row) && is_array($row['days'] ?? null) ? array_values($row['days']) : [];
            $open = is_array($row) ? self::time($row['open'] ?? '') : '';
            $close = is_array($row) ? self::time($row['close'] ?? '') : '';
            $daysOk = $days !== [] && count(array_unique($days)) === count($days)
                && array_diff($days, self::DAYS) === [];
            if (!$daysOk || !self::interval($open, $close)) {
                $bad[] = $n + 1;
                continue;
            }
            $out[] = ['days' => array_map('strval', $days), 'open' => $open, 'close' => $close];
        }
        return $out;
    }

    /**
     * Text lines "2026-12-31 closed", "2026-12-24 10:00-14:00" or REST exceptions; one per date (the first kept).
     *
     * @return list<array{date: string, closed?: true, open?: string, close?: string}>
     */
    public static function exceptions(mixed $v, ?array &$bad = null): array
    {
        $bad = [];
        $out = [];
        $seen = [];
        foreach (self::rows($v) as $n => $row) {
            if (is_string($row)) {
                $t = preg_split('/\s+/', trim($row), 2) ?: [];
                $rest = strtolower(trim($t[1] ?? ''));
                $row = ['date' => $t[0] ?? ''];
                if (in_array($rest, ['closed', 'close', 'off'], true)) {
                    $row['closed'] = true;
                } elseif (preg_match('/^(\S+?)\s*[-\x{2013}\x{2014}]\s*(\S+)$/u', $rest, $m) === 1) {
                    $row['open'] = $m[1];
                    $row['close'] = $m[2];
                }
            }
            $date = is_array($row) ? trim((string) ($row['date'] ?? '')) : '';
            $ok = self::isDate($date) && !isset($seen[$date]);
            $e = ['date' => $date];
            $timed = is_array($row) && (isset($row['open']) || isset($row['close']));
            if ($ok && is_array($row) && ($row['closed'] ?? null) === true && !$timed) {
                $e['closed'] = true;
            } elseif ($ok && $timed && !isset($row['closed'])) {
                $e['open'] = self::time($row['open'] ?? '');
                $e['close'] = self::time($row['close'] ?? '');
                $ok = self::interval($e['open'], $e['close']);
            } else {
                $ok = false;
            }
            if (!$ok) {
                $bad[] = $n + 1;
                continue;
            }
            $seen[$date] = true;
            $out[] = $e;
        }
        return $out;
    }

    /**
     * @return array{provider?: string, lat?: float, lng?: float}
     */
    public static function map(mixed $v): array
    {
        $v = is_array($v) ? $v : [];
        $out = [];
        $provider = strtolower(trim(is_scalar($v['provider'] ?? null) ? (string) $v['provider'] : ''));
        if (preg_match(self::IDENT, $provider) === 1) {
            $out['provider'] = $provider;
        }
        $lat = self::coord($v['lat'] ?? null, 90);
        $lng = self::coord($v['lng'] ?? null, 180);
        if ($lat !== null && $lng !== null) {
            $out['lat'] = $lat;
            $out['lng'] = $lng;
        }
        return $out;
    }

    /**
     * The core contact block from the options, only what is set (SettingsV04 contact, hours, hours_exceptions, map);
     * values pass the sanitizers again, so a hand-edited option never yields an invalid record.
     *
     * @return array{contact?: array<string, mixed>, hours?: list<mixed>, hours_exceptions?: list<mixed>,
     *   map?: array<string, mixed>}
     */
    public static function block(): array
    {
        $contact = array_filter([
            'phone' => self::phone(get_option('wpfasty_contact_phone', '')),
            'email' => self::email(get_option('wpfasty_contact_email', '')),
            'address' => self::line(get_option('wpfasty_contact_address', '')),
            'links' => self::links(get_option('wpfasty_contact_links', [])),
        ], static fn ($v): bool => $v !== '' && $v !== []);
        return array_filter([
            'contact' => $contact,
            'hours' => self::hours(get_option('wpfasty_hours', [])),
            'hours_exceptions' => self::exceptions(get_option('wpfasty_hours_exceptions', [])),
            'map' => self::map(get_option('wpfasty_map', [])),
        ], static fn ($v): bool => $v !== []);
    }

    /** @param list<array{network: string, handle: string}> $links */
    public static function linksText(array $links): string
    {
        return implode("\n", array_map(static fn (array $l): string => $l['network'] . ' ' . $l['handle'], $links));
    }

    /** @param list<array{days: list<string>, open: string, close: string}> $hours */
    public static function hoursText(array $hours): string
    {
        return implode("\n", array_map(
            static fn (array $r): string => self::daysText($r['days']) . ' ' . $r['open'] . '-' . $r['close'],
            $hours,
        ));
    }

    /** @param list<array<string, mixed>> $exceptions */
    public static function exceptionsText(array $exceptions): string
    {
        return implode("\n", array_map(
            static fn (array $e): string => $e['date'] . ' '
                . (($e['closed'] ?? false) === true ? 'closed' : $e['open'] . '-' . $e['close']),
            $exceptions,
        ));
    }

    /** "mon-fri" for consecutive days in week order, else the days as given ("sat,mon") */
    public static function daysText(array $days): string
    {
        $idx = array_map(static fn (string $d): int => (int) array_search($d, self::DAYS, true), $days);
        $run = count($idx) > 2;
        for ($i = 1; $run && $i < count($idx); $i++) {
            $run = $idx[$i] === $idx[$i - 1] + 1;
        }
        return $run ? $days[0] . '-' . $days[count($days) - 1] : implode(',', $days);
    }

    /** @return list<string> "mon-fri" → mon…fri, "fri-mon" wraps, "sat,sun" as given; [] when a part is unknown */
    public static function days(string $spec): array
    {
        $out = [];
        foreach (explode(',', strtolower($spec)) as $part) {
            $ends = explode('-', trim($part));
            $a = array_search($ends[0], self::DAYS, true);
            $b = array_search($ends[count($ends) - 1], self::DAYS, true);
            if ($a === false || $b === false || count($ends) > 2) {
                return [];
            }
            for ($i = $a;; $i = ($i + 1) % 7) {
                $out[] = self::DAYS[$i];
                if ($i === $b) {
                    break;
                }
            }
        }
        return $out;
    }

    public static function menu(): void
    {
        add_options_page(
            __('Contact & hours', 'wpfasty-types'),
            __('Contact & hours', 'wpfasty-types'),
            'manage_options',
            self::PAGE,
            [self::class, 'page'],
        );
    }

    public static function fields(): void
    {
        add_settings_section('contact', __('Contact', 'wpfasty-types'), static function (): void {
            echo '<p>' . esc_html__('Part of the Codex site settings.', 'wpfasty-types') . ' '
                . esc_html__('Builder tokens: {site:phone}, {site:email}, {site:address},', 'wpfasty-types') . ' '
                . esc_html__('{tel}, {mailto}, {whatsapp}, {telegram} and {map}.', 'wpfasty-types') . '</p>';
        }, self::PAGE);
        add_settings_section('hours', __('Opening hours', 'wpfasty-types'), static function (): void {
            echo '<p>' . esc_html__(
                'In the site time zone (Settings → General). Shown by {site:hours} and {open-now}.',
                'wpfasty-types'
            ) . '</p>';
        }, self::PAGE);
        add_settings_section('map', __('Map', 'wpfasty-types'), static function (): void {
            echo '<p>' . esc_html__('Without coordinates the {map} link searches the address.', 'wpfasty-types')
                . '</p>';
        }, self::PAGE);
        $input = static function (string $name, string $type, string $hint, string $value): void {
            printf(
                '<input name="%1$s" id="%1$s" type="%2$s" class="regular-text" value="%3$s">'
                    . ' <p class="description">%4$s</p>',
                esc_attr($name),
                esc_attr($type),
                esc_attr($value),
                esc_html($hint)
            );
        };
        $area = static function (string $name, string $hint, string $value, string $placeholder): void {
            printf(
                '<textarea name="%1$s" id="%1$s" rows="4" class="large-text code" placeholder="%4$s">%3$s</textarea>'
                    . ' <p class="description">%2$s</p>',
                esc_attr($name),
                esc_html($hint),
                esc_textarea($value),
                esc_attr($placeholder)
            );
        };
        $field = static fn (string $id, string $label, string $section, callable $cb) => add_settings_field(
            $id,
            $label,
            $cb,
            self::PAGE,
            $section,
            ['label_for' => $id],
        );
        $field('wpfasty_contact_phone', __('Phone', 'wpfasty-types'), 'contact', static fn () => $input(
            'wpfasty_contact_phone',
            'tel',
            __('International format E.164, e.g. +12025550143 (spaces are removed).', 'wpfasty-types'),
            (string) get_option('wpfasty_contact_phone', ''),
        ));
        $field('wpfasty_contact_email', __('Email', 'wpfasty-types'), 'contact', static fn () => $input(
            'wpfasty_contact_email',
            'email',
            __('Public contact address.', 'wpfasty-types'),
            (string) get_option('wpfasty_contact_email', ''),
        ));
        $field('wpfasty_contact_address', __('Address', 'wpfasty-types'), 'contact', static fn () => $input(
            'wpfasty_contact_address',
            'text',
            __('One line, in the site language.', 'wpfasty-types'),
            (string) get_option('wpfasty_contact_address', ''),
        ));
        $links = __('Messengers and profiles', 'wpfasty-types');
        $field('wpfasty_contact_links', $links, 'contact', static fn () => $area(
            'wpfasty_contact_links',
            __('One per line: network and handle, e.g. "telegram example_shop".', 'wpfasty-types') . ' '
                . __('WhatsApp takes the number: "whatsapp +12025550143". The order is kept.', 'wpfasty-types'),
            self::linksText(self::links(get_option('wpfasty_contact_links', []))),
            "telegram example_shop\nwhatsapp +12025550143",
        ));
        $field('wpfasty_hours', __('Weekly hours', 'wpfasty-types'), 'hours', static fn () => $area(
            'wpfasty_hours',
            __('One rule per line: days (mon … sun, ranges with -, lists with ,) and HH:MM-HH:MM.', 'wpfasty-types')
                . ' ' . __('After midnight: 20:00-02:00; all day: 00:00-24:00.', 'wpfasty-types')
                . ' ' . __('Days without a rule are closed.', 'wpfasty-types'),
            self::hoursText(self::hours(get_option('wpfasty_hours', []))),
            "mon-fri 09:00-18:00\nsat 10:00-14:00",
        ));
        $field('wpfasty_hours_exceptions', __('Exceptions', 'wpfasty-types'), 'hours', static fn () => $area(
            'wpfasty_hours_exceptions',
            __('One date per line: YYYY-MM-DD closed, or YYYY-MM-DD HH:MM-HH:MM.', 'wpfasty-types'),
            self::exceptionsText(self::exceptions(get_option('wpfasty_hours_exceptions', []))),
            "2026-12-25 closed\n2026-12-24 09:00-13:00",
        ));
        $field('wpfasty_map', __('Map pin', 'wpfasty-types'), 'map', [self::class, 'mapField']);
    }

    public static function mapField(): void
    {
        $m = self::map(get_option('wpfasty_map', []));
        $labels = [
            '' => __('Default (OpenStreetMap)', 'wpfasty-types'), 'osm' => 'OpenStreetMap', 'google' => 'Google Maps',
            'yandex' => 'Yandex Maps', 'apple' => 'Apple Maps',
        ];
        echo '<select name="wpfasty_map[provider]" id="wpfasty_map">';
        foreach ($labels as $value => $label) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr($value),
                ($m['provider'] ?? '') === $value ? ' selected' : '',
                esc_html($label)
            );
        }
        echo '</select> ';
        $coords = ['lat' => __('Latitude', 'wpfasty-types'), 'lng' => __('Longitude', 'wpfasty-types')];
        foreach ($coords as $k => $label) {
            printf(
                '<label>%1$s <input name="wpfasty_map[%2$s]" type="number" step="any" class="small-text"'
                    . ' value="%3$s"></label> ',
                esc_html($label),
                esc_attr($k),
                esc_attr(isset($m[$k]) ? (string) $m[$k] : '')
            );
        }
        echo '<p class="description">'
            . esc_html__('Latitude -90 … 90, longitude -180 … 180 (WGS 84), both or none.', 'wpfasty-types')
            . '</p>';
    }

    public static function page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="wrap"><h1>' . esc_html__('Contact & hours', 'wpfasty-types') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        do_settings_sections(self::PAGE);
        submit_button();
        echo '</form></div>';
    }

    public static function savePhone(mixed $v): string
    {
        $out = self::phone($v);
        self::report(
            $out === '' && is_scalar($v) && trim((string) $v) !== '',
            'phone',
            __('The phone is not an international number (E.164) and was not saved.', 'wpfasty-types')
        );
        return $out;
    }

    public static function saveEmail(mixed $v): string
    {
        $out = self::email($v);
        self::report(
            $out === '' && is_scalar($v) && trim((string) $v) !== '',
            'email',
            __('The email address is not valid and was not saved.', 'wpfasty-types')
        );
        return $out;
    }

    public static function saveAddress(mixed $v): string
    {
        return self::line($v);
    }

    /** @return list<array{network: string, handle: string}> */
    public static function saveLinks(mixed $v): array
    {
        $out = self::links($v, $bad);
        self::reportLines($bad, 'links', __('Messengers and profiles', 'wpfasty-types'));
        return $out;
    }

    /** @return list<array{days: list<string>, open: string, close: string}> */
    public static function saveHours(mixed $v): array
    {
        $out = self::hours($v, $bad);
        self::reportLines($bad, 'hours', __('Weekly hours', 'wpfasty-types'));
        return $out;
    }

    /** @return list<array<string, mixed>> */
    public static function saveExceptions(mixed $v): array
    {
        $out = self::exceptions($v, $bad);
        self::reportLines($bad, 'exceptions', __('Exceptions', 'wpfasty-types'));
        return $out;
    }

    /** @return array<string, mixed> */
    public static function saveMap(mixed $v): array
    {
        $out = self::map($v);
        $given = is_array($v) && (trim((string) ($v['lat'] ?? '')) !== '' || trim((string) ($v['lng'] ?? '')) !== '');
        self::report(
            $given && !isset($out['lat']),
            'map',
            __('The coordinates are out of range or incomplete and were not saved.', 'wpfasty-types')
        );
        return $out;
    }

    /** @return list<mixed> list items, or the non-empty lines of a text */
    private static function rows(mixed $v): array
    {
        if (is_string($v)) {
            $lines = array_map('trim', preg_split('/\R/u', $v) ?: []);
            return array_values(array_filter($lines, static fn (string $l): bool => $l !== ''));
        }
        return is_array($v) && array_is_list($v) ? $v : [];
    }

    /** "9:00" → "09:00"; "" when not a time (24:00 kept for close) */
    private static function time(mixed $v): string
    {
        $s = is_scalar($v) ? trim((string) $v) : '';
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $s, $m) !== 1) {
            return '';
        }
        $t = str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2];
        return preg_match(self::CLOSE, $t) === 1 ? $t : '';
    }

    private static function interval(string $open, string $close): bool
    {
        return preg_match(self::TIME, $open) === 1 && preg_match(self::CLOSE, $close) === 1 && $open !== $close;
    }

    private static function isDate(string $d): bool
    {
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $d, $m) === 1
            && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }

    private static function coord(mixed $v, int $max): ?float
    {
        if (is_string($v)) {
            $v = trim(str_replace(',', '.', $v));
        }
        if (!is_numeric($v)) {
            return null;
        }
        $f = (float) $v;
        return is_finite($f) && abs($f) <= $max ? $f : null;
    }

    private static function report(bool $failed, string $code, string $message): void
    {
        if ($failed && function_exists('add_settings_error')) {
            add_settings_error(self::GROUP, 'wpfasty_' . $code, $message);
        }
    }

    /** @param list<int> $bad */
    private static function reportLines(array $bad, string $code, string $field): void
    {
        self::report($bad !== [], $code, sprintf(
            /* translators: 1: field name, 2: line numbers */
            __('%1$s: lines %2$s were not understood and were not saved.', 'wpfasty-types'),
            $field,
            implode(', ', $bad)
        ));
    }
}
