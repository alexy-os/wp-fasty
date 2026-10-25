<?php

declare(strict_types=1);

namespace WPFastyTypes\Codex;

/**
 * Canonical form + digest, profile codex-entry/v1 (@ui8kit/contract docs/canonical.md, reference src/canonical.ts):
 * public projection (private metadata dropped, empty optional TOP-LEVEL fields omitted, metadata values untouched),
 * UTC dates without fractions, terms sorted by (taxonomy, term_id) in code point order, RFC 8785 serialization.
 * Terms use the same rules over the term keys (codex-term/v1). The manifest digest is JCS of Canonical().
 */
final class Canonical
{
    private const RFC3339 = '/^(\d{4})-(\d{2})-(\d{2})[Tt](\d{2}):(\d{2}):(\d{2})(\.\d+)?'
        . '([Zz]|([+-])(\d{2}):(\d{2}))$/';

    /** @throws CanonicalError */
    public static function canonicalize(string $text): string
    {
        return Json::jcs(self::entry(Json::parse($text)));
    }

    /** @throws CanonicalError */
    public static function digest(string $text): string
    {
        return Profile::ENTRY_PROFILE . ':sha256:' . hash('sha256', self::canonicalize($text));
    }

    /** Canonical bytes of an entry value tree built in PHP (the mapper). */
    public static function entryBytes(mixed $entry): string
    {
        return Json::jcs(self::entry($entry));
    }

    public static function termBytes(mixed $term): string
    {
        return Json::jcs(self::record($term, Profile::TERM_KEYS, Profile::TERM_REQUIRED));
    }

    /**
     * Canonical() of a manifest (codex-manifest/v2, contract a70c243 fixtures/vectors/manifest.json): the full manifest
     * in RFC 8785 — arrays (resources included) keep the order of the source, empty nested values are kept.
     *
     * @throws CanonicalError
     */
    public static function manifest(string $text): string
    {
        return Json::jcs(Json::parse($text));
    }

    /** codex-manifest/v2:sha256: + sha256(JCS(Canonical())) of a manifest value tree (objects as \stdClass). */
    public static function manifestDigest(mixed $manifest): string
    {
        return Profile::MANIFEST_DIGEST . hash('sha256', Json::jcs($manifest));
    }

    /** @return array<string, mixed> */
    private static function arr(mixed $v): array
    {
        return $v instanceof \stdClass ? get_object_vars($v) : (array) $v;
    }

    private static function isObj(mixed $v): bool
    {
        return $v instanceof \stdClass || (is_array($v) && ($v === [] ? false : !array_is_list($v)));
    }

    private static function isEmpty(mixed $v): bool
    {
        return $v === null || $v === '' || $v === [] || ($v instanceof \stdClass && get_object_vars($v) === []);
    }

    /** @param list<string> $keys */
    private static function closed(array $o, array $keys, string $path): void
    {
        foreach (array_keys($o) as $k) {
            if (!in_array((string) $k, $keys, true)) {
                throw new CanonicalError(
                    'canonical.unknown_field',
                    "{$path}.{$k}",
                    'unknown field ' . json_encode((string) $k),
                );
            }
        }
    }

    private static function entry(mixed $doc): \stdClass
    {
        return self::record($doc, Profile::ENTRY_KEYS, Profile::ENTRY_REQUIRED);
    }

    /**
     * @param list<string> $keys
     * @param list<string> $required
     */
    private static function record(mixed $doc, array $keys, array $required): \stdClass
    {
        if (!self::isObj($doc)) {
            throw new CanonicalError('canonical.invalid_type', '$', 'record must be a JSON object');
        }
        $o = self::arr($doc);
        self::closed($o, $keys, '$');
        foreach ($required as $k) {
            if (!array_key_exists($k, $o) || $o[$k] === null) {
                throw new CanonicalError('canonical.missing_field', "$.{$k}", "required field {$k} is missing");
            }
        }
        if (!is_string($o['id']) || preg_match(Profile::ID_PATTERN, $o['id']) !== 1) {
            throw new CanonicalError(
                'canonical.invalid_id',
                '$.id',
                'id must match ^[A-Za-z0-9][A-Za-z0-9._:-]{0,127}$',
            );
        }
        $out = new \stdClass();
        foreach ($o as $k => $v) {
            $k = (string) $k;
            $path = "$.{$k}";
            if (!in_array($k, $required, true) && self::isEmpty($v)) {
                continue;
            }
            if (in_array($k, Profile::DATE_FIELDS, true)) {
                $v = self::date($v, $path);
            } elseif ($k === 'metadata') {
                $v = self::metadata($v, $path);
                if ($v === null) {
                    continue;
                }
            } elseif ($k === 'terms') {
                $v = self::terms($v, $path);
            } elseif ($k === 'origin') {
                if (!is_array($v) || !array_is_list($v)) {
                    throw new CanonicalError('canonical.invalid_type', $path, 'origin must be an array');
                }
                foreach ($v as $n => $x) {
                    if (!self::isObj($x)) {
                        throw new CanonicalError(
                            'canonical.invalid_type',
                            "{$path}[{$n}]",
                            'origin item must be {system, id}',
                        );
                    }
                    self::closed(self::arr($x), ['system', 'id'], "{$path}[{$n}]");
                }
            } elseif ($k === 'discussion' && self::isObj($v)) {
                self::closed(self::arr($v), ['comments', 'pings'], $path);
            }
            $out->{$k} = $v;
        }
        return $out;
    }

    private static function metadata(mixed $v, string $path): ?\stdClass
    {
        if (!self::isObj($v)) {
            throw new CanonicalError('canonical.invalid_type', $path, 'metadata must be an object');
        }
        $md = new \stdClass();
        foreach (self::arr($v) as $mk => $mv) {
            $mk = (string) $mk;
            if (!self::isObj($mv) || !array_key_exists('value', self::arr($mv))) {
                throw new CanonicalError(
                    'canonical.invalid_type',
                    "{$path}.{$mk}",
                    'metadata item must be {value, private?}',
                );
            }
            $item = self::arr($mv);
            self::closed($item, ['value', 'private'], "{$path}.{$mk}");
            if (array_key_exists('private', $item) && !is_bool($item['private'])) {
                throw new CanonicalError(
                    'canonical.invalid_type',
                    "{$path}.{$mk}.private",
                    'private must be boolean',
                );
            }
            if (($item['private'] ?? false) === true) {
                continue; // not part of the public projection
            }
            $md->{$mk} = (object) ['value' => $item['value']];
        }
        return get_object_vars($md) === [] ? null : $md;
    }

    /** @return list<\stdClass> */
    private static function terms(mixed $v, string $path): array
    {
        if (!is_array($v) || !array_is_list($v)) {
            throw new CanonicalError('canonical.invalid_type', $path, 'terms must be an array');
        }
        $out = [];
        foreach ($v as $n => $t) {
            $a = self::isObj($t) ? self::arr($t) : [];
            if (!is_string($a['taxonomy'] ?? null) || !is_string($a['term_id'] ?? null)) {
                throw new CanonicalError(
                    'canonical.invalid_type',
                    "{$path}[{$n}]",
                    'term must be {taxonomy, term_id} strings',
                );
            }
            self::closed($a, ['taxonomy', 'term_id'], "{$path}[{$n}]");
            $out[] = (object) $a;
        }
        // UTF-8 byte order == Unicode code point order
        usort($out, static fn (\stdClass $a, \stdClass $b): int
            => strcmp($a->taxonomy, $b->taxonomy) ?: strcmp($a->term_id, $b->term_id));
        return $out;
    }

    /** RFC 3339 → YYYY-MM-DDTHH:MM:SSZ (UTC); zero fractions dropped, others rejected. */
    public static function date(mixed $v, string $path = '$'): string
    {
        if (!is_string($v)) {
            throw new CanonicalError('canonical.invalid_date', $path, 'date-time must be a string');
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1) {
            throw new CanonicalError('canonical.invalid_date', $path, "date without time: {$v}");
        }
        if (preg_match(self::RFC3339, $v, $m) !== 1) {
            throw new CanonicalError('canonical.invalid_date', $path, "not an RFC 3339 date-time: {$v}");
        }
        if (($m[7] ?? '') !== '' && preg_match('/[1-9]/', $m[7]) === 1) {
            throw new CanonicalError('canonical.fractional_seconds', $path, "fractional seconds are not allowed: {$v}");
        }
        [$y, $mo, $d, $h, $mi, $s] = array_map('intval', array_slice($m, 1, 6));
        $oh = (int) ($m[10] ?? 0);
        $om = (int) ($m[11] ?? 0);
        if (!checkdate($mo, $d, $y) || $h > 23 || $mi > 59 || $s > 59 || $oh > 23 || $om > 59) {
            throw new CanonicalError('canonical.invalid_date', $path, "date-time out of range: {$v}");
        }
        $t = gmmktime($h, $mi, $s, $mo, $d, $y);
        $off = ($oh * 60 + $om) * 60;
        $t += ($m[9] ?? '') === '+' ? -$off : (($m[9] ?? '') === '-' ? $off : 0);
        $yy = (int) gmdate('Y', $t);
        if ($yy < 0 || $yy > 9999) {
            throw new CanonicalError('canonical.invalid_date', $path, "year out of range after UTC conversion: {$v}");
        }
        return gmdate('Y-m-d\TH:i:s\Z', $t);
    }
}
