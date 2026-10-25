<?php

declare(strict_types=1);

namespace WPFastyTypes\Codex;

/**
 * Strict JSON (RFC 8259) in, RFC 8785 (JCS) out — without json_decode, which merges duplicate keys, loses
 * integers beyond 2^53 and turns {} / {"0":…} into PHP lists. Objects are \stdClass after parse(); on encode
 * a \stdClass or a non-list PHP array is an object, a list is an array (so build empty objects as \stdClass).
 */
final class Json
{
    private string $s;
    private int $i = 0;
    private int $n;

    private function __construct(string $text)
    {
        $this->s = $text;
        $this->n = strlen($text);
    }

    /** @throws CanonicalError */
    public static function parse(string $text): mixed
    {
        if (preg_match('//u', $text) !== 1) {
            throw new CanonicalError('canonical.invalid_json', '@0', 'text is not UTF-8');
        }
        $p = new self($text);
        $v = $p->value(0);
        $p->ws();
        if ($p->i !== $p->n) {
            $p->fail('canonical.invalid_json', 'trailing data after the JSON value');
        }
        return $v;
    }

    private function fail(string $code, string $msg, ?int $at = null): never
    {
        throw new CanonicalError($code, '@' . ($at ?? $this->i), $msg);
    }

    private function ws(): void
    {
        while ($this->i < $this->n && strpos(" \t\n\r", $this->s[$this->i]) !== false) {
            $this->i++;
        }
    }

    private function value(int $depth): mixed
    {
        if ($depth > 512) {
            $this->fail('canonical.invalid_json', 'nesting too deep');
        }
        $this->ws();
        $c = $this->s[$this->i] ?? '';
        if ($c === '{') {
            return $this->object($depth);
        }
        if ($c === '[') {
            $this->i++;
            $a = [];
            $this->ws();
            if (($this->s[$this->i] ?? '') === ']') {
                $this->i++;
                return $a;
            }
            for (;;) {
                $a[] = $this->value($depth + 1);
                $this->ws();
                $c = $this->s[$this->i++] ?? '';
                if ($c === ']') {
                    return $a;
                }
                if ($c !== ',') {
                    $this->fail('canonical.invalid_json', "expected ',' or ']'", $this->i - 1);
                }
            }
        }
        if ($c === '"') {
            return $this->str();
        }
        foreach (['true' => true, 'false' => false, 'null' => null] as $word => $v) {
            if (substr_compare($this->s, $word, $this->i, strlen($word)) === 0) {
                $this->i += strlen($word);
                return $v;
            }
        }
        if ($c === '-' || ($c >= '0' && $c <= '9' && $c !== '')) {
            return $this->num();
        }
        $this->fail('canonical.invalid_json', $c === '' ? 'unexpected end of input' : 'unexpected token');
    }

    private function object(int $depth): \stdClass
    {
        $this->i++;
        $o = new \stdClass();
        $seen = [];
        $this->ws();
        if (($this->s[$this->i] ?? '') === '}') {
            $this->i++;
            return $o;
        }
        for (;;) {
            $this->ws();
            if (($this->s[$this->i] ?? '') !== '"') {
                $this->fail('canonical.invalid_json', 'expected a key');
            }
            $at = $this->i;
            $k = $this->str();
            if (isset($seen[$k])) {
                $this->fail('canonical.duplicate_key', 'duplicate key ' . json_encode($k), $at);
            }
            $seen[$k] = true;
            $this->ws();
            if (($this->s[$this->i] ?? '') !== ':') {
                $this->fail('canonical.invalid_json', "expected ':'");
            }
            $this->i++;
            $o->{$k} = $this->value($depth + 1);
            $this->ws();
            $c = $this->s[$this->i++] ?? '';
            if ($c === '}') {
                return $o;
            }
            if ($c !== ',') {
                $this->fail('canonical.invalid_json', "expected ',' or '}'", $this->i - 1);
            }
        }
    }

    private function str(): string
    {
        $start = $this->i++;
        $out = '';
        $units = []; // pending UTF-16 code units of \u escapes
        $flush = function () use (&$units, &$out, $start): void {
            for ($k = 0, $m = count($units); $k < $m; $k++) {
                $u = $units[$k];
                $next = $units[$k + 1] ?? 0;
                if ($u >= 0xD800 && $u <= 0xDBFF && $next >= 0xDC00 && $next <= 0xDFFF) {
                    $out .= self::utf8(0x10000 + (($u - 0xD800) << 10) + ($units[++$k] - 0xDC00));
                } elseif ($u >= 0xD800 && $u <= 0xDFFF) {
                    $this->fail('canonical.invalid_string', 'lone surrogate in string', $start);
                } else {
                    $out .= self::utf8($u);
                }
            }
            $units = [];
        };
        for (;;) {
            if ($this->i >= $this->n) {
                $this->fail('canonical.invalid_json', 'unterminated string', $start);
            }
            $c = $this->s[$this->i];
            if ($c === '"') {
                $this->i++;
                break;
            }
            if (ord($c) < 0x20) {
                $this->fail('canonical.invalid_json', 'raw control character in string');
            }
            if ($c !== '\\') {
                $flush();
                $len = strcspn($this->s, "\"\\" . implode('', array_map('chr', range(0, 31))), $this->i);
                $out .= substr($this->s, $this->i, $len);
                $this->i += $len;
                continue;
            }
            $e = $this->s[$this->i + 1] ?? '';
            $this->i += 2;
            if ($e === 'u') {
                $h = substr($this->s, $this->i, 4);
                if (preg_match('/^[0-9A-Fa-f]{4}$/', $h) !== 1) {
                    $this->fail('canonical.invalid_json', 'bad \\u escape');
                }
                $units[] = hexdec($h);
                $this->i += 4;
                continue;
            }
            $map = [
                '"' => '"', '\\' => '\\', '/' => '/', 'b' => "\x08", 'f' => "\f", 'n' => "\n", 'r' => "\r", 't' => "\t",
            ];
            if (!isset($map[$e])) {
                $this->fail('canonical.invalid_json', 'bad escape', $this->i - 2);
            }
            $flush();
            $out .= $map[$e];
        }
        $flush();
        return $out;
    }

    private function num(): int|float
    {
        if (preg_match('/-?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][+-]?[0-9]+)?/A', $this->s, $m, 0, $this->i) !== 1) {
            $this->fail('canonical.invalid_json', 'bad number');
        }
        $tok = $m[0];
        $at = $this->i;
        $this->i += strlen($tok);
        $frac = ($m[2] ?? '') !== '';
        $exp = ($m[3] ?? '') !== '';
        if (!$frac && !$exp) {
            $digits = ltrim($tok, '-');
            if (strlen($digits) > 16 || (int) $digits > Profile::MAX_SAFE) {
                $this->fail('canonical.unsafe_integer', "integer {$tok} is outside ±(2^53-1)", $at);
            }
            return (int) $tok;
        }
        $f = (float) $tok;
        if (!is_finite($f)) {
            $this->fail('canonical.invalid_number', "number {$tok} is not finite", $at);
        }
        if (!$exp && floor($f) === $f && abs($f) > Profile::MAX_SAFE) {
            $this->fail('canonical.unsafe_integer', "integer {$tok} is outside ±(2^53-1)", $at);
        }
        return $f;
    }

    // ---------- RFC 8785 ----------

    /** JCS text of a value tree (\stdClass / assoc array = object, list = array). */
    public static function jcs(mixed $v): string
    {
        if ($v === null) {
            return 'null';
        }
        if (is_bool($v)) {
            return $v ? 'true' : 'false';
        }
        if (is_int($v)) {
            if (abs($v) > Profile::MAX_SAFE) {
                throw new CanonicalError('canonical.unsafe_integer', '$', "integer {$v} is outside ±(2^53-1)");
            }
            return (string) $v;
        }
        if (is_float($v)) {
            return self::number($v);
        }
        if (is_string($v)) {
            return self::string($v);
        }
        if (is_array($v) && array_is_list($v)) {
            return '[' . implode(',', array_map([self::class, 'jcs'], $v)) . ']';
        }
        if (is_array($v) || $v instanceof \stdClass) {
            $o = (array) $v;
            $keys = array_map('strval', array_keys($o));
            usort($keys, [self::class, 'utf16Compare']);
            $parts = [];
            foreach ($keys as $k) {
                $parts[] = self::string($k) . ':' . self::jcs($o[$k]);
            }
            return '{' . implode(',', $parts) . '}';
        }
        throw new CanonicalError('canonical.invalid_type', '$', 'value is not JSON: ' . get_debug_type($v));
    }

    /** ECMAScript Number::toString of a finite double (RFC 8785 §3.2.2.3); -0 → "0". */
    public static function number(float $f): string
    {
        if (!is_finite($f)) {
            throw new CanonicalError('canonical.invalid_number', '$', 'number is not finite');
        }
        if ($f == 0.0) {
            return '0';
        }
        $prev = ini_get('serialize_precision');
        ini_set('serialize_precision', '-1'); // shortest round-trip digits
        $repr = (string) json_encode($f);
        ini_set('serialize_precision', (string) $prev);
        preg_match('/^(-?)(\d+)(?:\.(\d+))?(?:[eE]([+-]?\d+))?$/', $repr, $m);
        $sign = $m[1];
        $int = $m[2];
        $fr = $m[3] ?? '';
        $e = (int) ($m[4] ?? 0);
        $digits = ltrim($int . $fr, '0');
        $point = strlen($int) + $e - (strlen($int . $fr) - strlen($digits)); // decimal point position in $digits
        $digits = rtrim($digits, '0');
        $k = strlen($digits);
        $n = $point;
        if ($k <= $n && $n <= 21) {
            $s = $digits . str_repeat('0', $n - $k);
        } elseif (0 < $n && $n <= 21) {
            $s = substr($digits, 0, $n) . '.' . substr($digits, $n);
        } elseif (-6 < $n && $n <= 0) {
            $s = '0.' . str_repeat('0', -$n) . $digits;
        } else {
            $x = $n - 1;
            $s = $digits[0] . ($k > 1 ? '.' . substr($digits, 1) : '') . 'e' . ($x >= 0 ? '+' : '-') . abs($x);
        }
        return $sign . $s;
    }

    /** JSON string: only " \ and controls escaped; / U+2028 U+2029 and non-ASCII as is. */
    public static function string(string $s): string
    {
        $out = json_encode(
            $s,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS | JSON_THROW_ON_ERROR,
        );
        return $out;
    }

    /** Compare two UTF-8 strings as sequences of UTF-16 code units (JCS key order). */
    public static function utf16Compare(string $a, string $b): int
    {
        // BMP only: UTF-8 byte order == code point order == UTF-16 order
        if (preg_match('/[\xF0-\xF4]/', $a . $b) !== 1) {
            return strcmp($a, $b);
        }
        return strcmp(self::utf16($a), self::utf16($b));
    }

    /** UTF-16BE bytes of a UTF-8 string (no mbstring needed). */
    private static function utf16(string $s): string
    {
        $out = '';
        preg_match_all('/./su', $s, $m);
        foreach ($m[0] as $ch) {
            $b = array_values((array) unpack('C*', $ch));
            $cp = match (count($b)) {
                1 => $b[0],
                2 => (($b[0] & 0x1F) << 6) | ($b[1] & 0x3F),
                3 => (($b[0] & 0x0F) << 12) | (($b[1] & 0x3F) << 6) | ($b[2] & 0x3F),
                default => (($b[0] & 0x07) << 18) | (($b[1] & 0x3F) << 12) | (($b[2] & 0x3F) << 6) | ($b[3] & 0x3F),
            };
            if ($cp >= 0x10000) {
                $cp -= 0x10000;
                $out .= pack('nn', 0xD800 | ($cp >> 10), 0xDC00 | ($cp & 0x3FF));
            } else {
                $out .= pack('n', $cp);
            }
        }
        return $out;
    }

    /** UTF-8 bytes of a code point. */
    private static function utf8(int $cp): string
    {
        return match (true) {
            $cp < 0x80 => chr($cp),
            $cp < 0x800 => chr(0xC0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3F)),
            $cp < 0x10000 => chr(0xE0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3F)) . chr(0x80 | ($cp & 0x3F)),
            default => chr(0xF0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3F)) . chr(0x80 | (($cp >> 6) & 0x3F))
                . chr(0x80 | ($cp & 0x3F)),
        };
    }
}
