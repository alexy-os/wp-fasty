<?php

declare(strict_types=1);

namespace WPFastyBuilder\Codex;

/**
 * PHP port of codegen-x core.ts readPath / fromCodex (Q4: paths and data pipes normative, format pipes informative):
 *   a.b.c                 dotted path; null = absent (undefined)
 *   |date |ago |ago-days |ago-short |number |compact      formatting (Format)
 *   |media |author |term:<tax> |term-slug:<tax> |route |menu  data pipes
 *   |excerpt-fallback |reading-minutes |term-names:<tax> |settings:<key> |in-stock   (Q4 additions)
 *   |html                 trusted markup: {"__html": string} (TrustedHtml, printed unescaped by og_html)
 * Options: now (epoch s or RFC 3339), locale, media[], users[], terms[], menus[],
 * routes{kind: "/x/{slug}"}, settings{}.
 * Records are JSON-decoded arrays (assoc for objects).
 */
final class ReadPath
{
    public const DEMO_NOW = '2026-03-30T12:00:00Z';
    public const DEFAULT_LOCALE = 'en';
    /** Words of a generated excerpt (excerpt-fallback) and words per reading minute (reading-minutes). */
    public const EXCERPT_WORDS = 24;
    public const WORDS_PER_MINUTE = 200;

    private static function field(mixed $o, string|int $k): mixed
    {
        return is_array($o) ? ($o[$k] ?? null) : null;
    }

    private static function at(mixed $source, string $dotted): mixed
    {
        $cur = $source;
        foreach (explode('.', $dotted) as $part) {
            if (!is_array($cur) || !array_key_exists($part, $cur)) {
                return null;
            }
            $cur = $cur[$part];
        }
        return $cur;
    }

    /**
     * @param list<mixed> $records
     */
    private static function byLocale(array $records, string $locale): mixed
    {
        foreach ($records as $r) {
            if (self::field($r, 'locale') === $locale) {
                return $r;
            }
        }
        return $records[0] ?? null;
    }

    /** @return list<string> */
    private static function words(mixed $html): array
    {
        $text = preg_replace('/<[^>]*>/', ' ', is_scalar($html) ? (string) $html : '');
        $parts = preg_split('/\s+/u', (string) $text) ?: [];
        return array_values(array_filter($parts, static fn ($w): bool => $w !== ''));
    }

    /**
     * Records of the `fields` mapping of a block slot (fromCodex).
     *
     * @param list<mixed>           $entries
     * @param array<string, string> $fields  record field → path
     * @param array<string, mixed>  $opts
     * @return list<array<string, mixed>>
     */
    public static function fromCodex(array $entries, array $fields, array $opts = []): array
    {
        $out = [];
        foreach ($entries as $e) {
            $rec = [];
            foreach ($fields as $name => $path) {
                $rec[(string) $name] = self::read($e, (string) $path, $opts);
            }
            $out[] = $rec;
        }
        return $out;
    }

    /** @param array<string, mixed> $opts */
    public static function read(mixed $source, string $path, array $opts = []): mixed
    {
        $bar = strpos($path, '|');
        $dotted = $bar === false ? $path : substr($path, 0, $bar);
        $pipe = $bar === false ? null : substr($path, $bar + 1);
        $cur = self::at($source, $dotted);
        $own = self::field($source, 'locale');
        $locale = (string) ($opts['locale'] ?? (is_string($own) ? $own : self::DEFAULT_LOCALE));

        // Q4 pipes that also run when the value is absent (fallbacks)
        if ($pipe === 'excerpt-fallback') {
            if (is_string($cur) && trim($cur) !== '') {
                return $cur;
            }
            $w = self::words(self::field($source, 'content'));
            if ($w === []) {
                return null;
            }
            return count($w) > self::EXCERPT_WORDS
                ? implode(' ', array_slice($w, 0, self::EXCERPT_WORDS)) . '…'
                : implode(' ', $w);
        }
        if ($pipe !== null && str_starts_with($pipe, 'settings:')) {
            return $cur ?? self::field($opts['settings'] ?? null, substr($pipe, 9));
        }
        if ($pipe === 'in-stock') {
            return $cur === null ? true : ((is_int($cur) || is_float($cur)) && $cur > 0);
        }
        if ($pipe === null || $cur === null) {
            return $cur;
        }
        if ($pipe === 'reading-minutes') {
            return (int) ceil(count(self::words($cur)) / self::WORDS_PER_MINUTE);
        }
        if (str_starts_with($pipe, 'term-names:')) {
            if (!is_array($cur)) {
                return null;
            }
            $tax = substr($pipe, 11);
            $names = [];
            foreach ($cur as $ref) {
                if (self::field($ref, 'taxonomy') === $tax) {
                    $name = self::field(self::term($opts, $tax, self::field($ref, 'term_id'), $locale), 'name');
                    if ($name !== null) {
                        $names[] = $name;
                    }
                }
            }
            return $names;
        }
        if ($pipe === 'html') {
            // markup of the Codex (the body of an entry) is trusted: the TrustedHtml marker the og_html helper of the
            // runtimes prints unescaped (codegen-x readPath `html`); a value read without this pipe stays text
            // only a string is markup: a number, a boolean, a list or null gives null (codegen-x readPath to follow)
            return is_string($cur) ? ['__html' => $cur] : null;
        }
        if ($pipe === 'date') {
            return Format::date($cur, $locale);
        }
        if ($pipe === 'ago' || $pipe === 'ago-days' || $pipe === 'ago-short') {
            $now = $opts['now'] ?? self::DEMO_NOW;
            $now = is_int($now) ? $now : (Format::time($now) ?? 0);
            return Format::ago($cur, $now, $pipe, $locale);
        }
        if ($pipe === 'media') {
            foreach ((array) ($opts['media'] ?? []) as $m) {
                if (self::field($m, 'id') === $cur && self::field($m, 'status') === 'published') {
                    return self::at($m, 'metadata.file.value');
                }
            }
            return null;
        }
        if ($pipe === 'author') {
            $users = array_values(array_filter(
                (array) ($opts['users'] ?? []),
                static fn ($u): bool => self::field($u, 'id') === $cur,
            ));
            $user = self::byLocale($users, $locale);
            return self::at($user, 'metadata.display_name.value') ?? self::field($user, 'title');
        }
        if ($pipe === 'number') {
            $currency = self::at($source, 'metadata.currency.value');
            return is_string($currency)
                ? Format::currency($cur, $currency, $locale)
                : Format::figure($cur, self::at($source, 'metadata.unit.value'), $locale);
        }
        if ($pipe === 'compact') {
            return Format::compact($cur, $locale);
        }
        if (str_starts_with($pipe, 'term:') || str_starts_with($pipe, 'term-slug:')) {
            $tax = substr($pipe, strpos($pipe, ':') + 1);
            $ref = null;
            foreach (is_array($cur) ? $cur : [] as $r) {
                if (self::field($r, 'taxonomy') === $tax) {
                    $ref = $r;
                    break;
                }
            }
            if ($ref === null) {
                return null;
            }
            $term = self::term($opts, $tax, self::field($ref, 'term_id'), $locale);
            return self::field($term, str_starts_with($pipe, 'term-slug:') ? 'slug' : 'name');
        }
        if ($pipe === 'menu') {
            $kids = array_values(array_filter(
                (array) ($opts['menus'] ?? []),
                static fn ($m): bool => self::field($m, 'parent_id') === $cur,
            ));
            $order = static fn ($m): int|float => is_int(self::field($m, 'order')) || is_float(self::field($m, 'order'))
                ? self::field($m, 'order') : 0;
            usort($kids, static fn ($a, $b): int => $order($a) <=> $order($b));
            return array_map(static fn ($m): array => [
                'id' => self::field($m, 'id'), 'name' => self::field($m, 'title'),
                'icon' => self::at($m, 'metadata.icon.value'), 'href' => self::read($m, 'slug|route', $opts),
            ], $kids);
        }
        if ($pipe === 'route') {
            $kind = self::field($source, 'kind');
            $route = is_string($kind) ? ($opts['routes'][$kind] ?? null) : null;
            if ($route === null) {
                return null;
            }
            return str_replace('{slug}', self::encodeUriComponent((string) $cur), (string) $route);
        }
        throw new \InvalidArgumentException("readPath: unknown formatter \"{$pipe}\" in \"{$path}\"");
    }

    /** @param array<string, mixed> $opts */
    private static function term(array $opts, string $tax, mixed $id, string $locale): mixed
    {
        $list = array_values(array_filter(
            (array) ($opts['terms'] ?? []),
            static fn ($t): bool => self::field($t, 'taxonomy_id') === $tax && self::field($t, 'id') === $id,
        ));
        return self::byLocale($list, $locale);
    }

    /** JS encodeURIComponent. */
    public static function encodeUriComponent(string $s): string
    {
        return strtr(rawurlencode($s), ['%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')']);
    }
}
