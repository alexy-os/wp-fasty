<?php

declare(strict_types=1);

namespace WPFastyBuilder\Codex;

/**
 * Formatting pipes of readPath (informative, Q4): the codegen-x core.ts formatters (fmtDate, fmtAgo, fmtFigure,
 * fmtCompact, fmtCurrency). English is written out (identical bytes to Intl en, checked by the draft vectors);
 * other locales use ext-intl when present, else English.
 */
final class Format
{
    private const ISO = '/^\d{4}-\d{2}-\d{2}(?:[Tt ]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:[Zz]|[+-]\d{2}:?\d{2})?)?$/';
    private const UNITS = [
        'percent' => '%', 'second' => 's', 'millisecond' => 'ms', 'minute' => 'm', 'hour' => 'h', 'day' => 'd',
        'kilobyte' => 'kB', 'megabyte' => 'MB', 'gigabyte' => 'GB',
    ];
    private const SYMBOL = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'JPY' => '¥', 'CNY' => 'CN¥', 'RUB' => 'RUB '];
    private const ZERO_DECIMAL = ['JPY', 'KRW', 'VND', 'CLP', 'ISK', 'HUF', 'TWD', 'UGX', 'XAF', 'XOF'];

    public static function english(string $locale): bool
    {
        return preg_match('/^en(-|$)/i', $locale) === 1;
    }

    /** Seconds since the epoch of an ISO date / date-time, null when the value is not one. */
    public static function time(mixed $iso): ?int
    {
        if (!is_string($iso) || preg_match(self::ISO, $iso) !== 1) {
            return null;
        }
        // date-only and zone-less forms: UTC (as Date.parse does for date-only)
        $t = strtotime(preg_match('/[Zz]|[+-]\d{2}:?\d{2}$/', $iso) === 1 || strlen($iso) === 10 ? $iso : $iso . 'Z');
        return $t === false ? null : $t;
    }

    public static function date(mixed $iso, string $locale): mixed
    {
        $t = self::time($iso);
        if ($t === null) {
            return $iso;
        }
        if (!self::english($locale) && class_exists(\IntlDateFormatter::class)) {
            $none = \IntlDateFormatter::NONE;
            $f = new \IntlDateFormatter($locale, $none, $none, 'UTC', null, 'MMM dd, y');
            $out = $f->format($t);
            if (is_string($out)) {
                return $out;
            }
        }
        return gmdate('M d, Y', $t);
    }

    public static function ago(mixed $iso, int $now, string $style, string $locale): mixed
    {
        $t = self::time($iso);
        if ($t === null) {
            return $iso;
        }
        $min = max(0, (int) round(($now - $t) / 60));
        $h = intdiv($min, 60);
        $d = intdiv($min, 1440);
        $w = intdiv($d, 7);
        if ($style === 'ago-days') {
            return $d < 7
                ? self::relative(max($d, 1), 'day', $locale, $style)
                : self::relative($w, 'week', $locale, $style);
        }
        if ($min < 60) {
            return self::relative($min, 'minute', $locale, $style);
        }
        if ($h < 24) {
            return self::relative($h, 'hour', $locale, $style);
        }
        if ($style === 'ago-short') {
            return self::relative($d, 'day', $locale, $style);
        }
        return $d < 7 ? self::relative($d, 'day', $locale, $style) : self::relative($w, 'week', $locale, $style);
    }

    private static function relative(int $n, string $unit, string $locale, string $style): string
    {
        $en = self::english($locale);
        if ($en && ($unit === 'minute' || $unit === 'hour' || ($unit === 'day' && $style === 'ago-short'))) {
            return $n . ' ' . ['minute' => 'min', 'hour' => 'h', 'day' => 'd'][$unit] . ' ago';
        }
        // Intl.RelativeTimeFormat en, long; numeric "auto" only in the "ago" style
        if ($style === 'ago' && $n === 1) {
            return $unit === 'day' ? 'Yesterday' : 'Last week';
        }
        if ($style === 'ago' && $n === 0) {
            return $unit === 'day' ? 'Today' : 'This week';
        }
        return $n . ' ' . $unit . ($n === 1 ? '' : 's') . ' ago';
    }

    /** "12,400" with max 2 fraction digits (en grouping). */
    public static function decimal(float|int $v, int $max = 2, int $min = 0): string
    {
        $s = number_format(abs((float) $v), $max, '.', ',');
        if ($max > $min && str_contains($s, '.')) {
            [$i, $f] = explode('.', $s);
            $f = rtrim($f, '0');
            $f = str_pad($f, $min, '0');
            $s = $f === '' ? $i : $i . '.' . $f;
        }
        $neg = $v < 0 && trim($s, '0.,') !== '';
        return ($neg ? '-' : '') . $s;
    }

    public static function figure(mixed $value, mixed $unit, string $locale): ?string
    {
        if (!is_int($value) && !is_float($value) || !is_finite((float) $value)) {
            return null;
        }
        $known = is_string($unit) && isset(self::UNITS[$unit]);
        if (!self::english($locale) && class_exists(\NumberFormatter::class) && !$known) {
            $f = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            $f->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
            return (string) $f->format($value);
        }
        return self::decimal($value) . ($known ? self::UNITS[$unit] : '');
    }

    public static function compact(mixed $value, string $locale): ?string
    {
        if (!is_int($value) && !is_float($value) || !is_finite((float) $value)) {
            return null;
        }
        $v = (float) $value;
        $abs = abs($v);
        $units = [1e12 => 'T', 1e9 => 'B', 1e6 => 'M', 1e3 => 'K'];
        $sign = $v < 0 ? '-' : '';
        if ($abs < 1000) {
            return $sign . self::decimal(round($abs, 1), 1);
        }
        $steps = array_keys($units);
        foreach ($steps as $k => $base) {
            if ($abs >= $base) {
                $n = round($abs / $base, 1);
                // 999,999 → "1000K" in plain rounding; Intl moves up to the next unit
                if ($n >= 1000 && $k > 0) {
                    return $sign . self::decimal(round($abs / $steps[$k - 1], 1), 1) . $units[$steps[$k - 1]];
                }
                return $sign . self::decimal($n, 1) . $units[$base];
            }
        }
        return $sign . self::decimal($abs, 1);
    }

    /** Integer minor units → "$48,200" / "$36.50" (whole amounts without cents). */
    public static function currency(mixed $minor, string $currency, string $locale): ?string
    {
        if (!is_int($minor) && !is_float($minor) || !is_finite((float) $minor)) {
            return null;
        }
        $cur = strtoupper($currency);
        $digits = in_array($cur, self::ZERO_DECIMAL, true) ? 0 : 2;
        $value = $minor / 10 ** $digits;
        $whole = floor($value) === (float) $value;
        if (!self::english($locale) && class_exists(\NumberFormatter::class)) {
            $f = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
            $f->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $whole ? 0 : $digits);
            $out = $f->formatCurrency($value, $cur);
            if (is_string($out)) {
                return $out;
            }
        }
        $sym = self::SYMBOL[$cur] ?? $cur . "\u{00A0}";
        $num = self::decimal(abs($value), $digits, $whole ? 0 : $digits);
        return ($value < 0 ? '-' : '') . $sym . $num;
    }
}
