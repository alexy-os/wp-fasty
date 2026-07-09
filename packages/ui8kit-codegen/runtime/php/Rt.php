<?php

/**
 * UI8Kit PHP runtime helpers — one implementation of every decision-table
 * primitive used by generated Latte and Twig templates. Mirrors utils/expr.go
 * and utils/expr.ts so all runtimes resolve defaults identically.
 */

declare(strict_types=1);

namespace UI8Kit;

final class Rt
{
    /** Allowed root tags per semantic group (mirrors src/domain/tags.ts). */
    public const GROUPS = [
        'Layout' => ['div', 'section', 'article', 'aside', 'header', 'footer', 'main', 'nav', 'figure', 'search', 'hgroup'],
        'BoxAllowed' => ['div'],
        'BlockText' => ['p', 'blockquote', 'figcaption', 'address', 'pre'],
        'Inline' => ['span', 'em', 'strong', 'small', 'abbr', 'cite', 'code', 'kbd', 'mark', 'time', 'data', 'var', 'samp', 'sub', 'sup', 'b', 'i', 'u', 's', 'q', 'dfn', 'bdo', 'bdi', 'ins', 'del'],
        'Heading' => ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'],
        'List' => ['ul', 'ol', 'dl', 'menu'],
        'ListItem' => ['li', 'dt', 'dd'],
        'Form' => ['form', 'fieldset'],
        'FormControl' => ['input', 'textarea', 'select', 'button', 'option', 'optgroup', 'datalist', 'output', 'meter', 'progress'],
        'FormLabel' => ['label', 'legend'],
        'Table' => ['table'],
        'TableSection' => ['thead', 'tbody', 'tfoot'],
        'TableRow' => ['tr'],
        'TableCell' => ['th', 'td'],
        'TableColumn' => ['colgroup', 'col'],
        'Media' => ['img', 'picture', 'source'],
        'Disclosure' => ['details', 'summary'],
        'Container' => ['div', 'main', 'section'],
        'Stack' => ['div', 'ul', 'ol'],
        'Group' => ['div', 'fieldset', 'dl'],
    ];

    /** Join non-empty class fragments with a single space (Go Cn). */
    public static function cn(?string ...$classes): string
    {
        $parts = [];
        foreach ($classes as $c) {
            $t = trim((string) ($c ?? ''));
            if ($t !== '') {
                $parts[] = $t;
            }
        }
        return implode(' ', $parts);
    }

    /** Omit-if-empty attribute value: '' → null so the attribute is dropped. */
    public static function orNull(?string $v): ?string
    {
        return trim((string) ($v ?? '')) === '' ? null : $v;
    }

    public static function isSetStr(?string $v): bool
    {
        return trim((string) ($v ?? '')) !== '';
    }

    public static function defaultIfEmpty(?string $v, string $fallback): string
    {
        $t = trim((string) ($v ?? ''));
        return $t === '' ? $fallback : $t;
    }

    public static function oneOfOr(?string $v, string $fallback, string ...$allowed): string
    {
        $t = trim((string) ($v ?? ''));
        return in_array($t, $allowed, true) ? $t : $fallback;
    }

    public static function oneOfOrLower(?string $v, string $fallback, string ...$allowed): string
    {
        return self::oneOfOr(strtolower(trim((string) ($v ?? ''))), $fallback, ...$allowed);
    }

    /** Decision table: mapping[trim(v)] ?? fallback. */
    public static function mapOr(?string $v, array $mapping, string $fallback): string
    {
        return $mapping[trim((string) ($v ?? ''))] ?? $fallback;
    }

    public static function mapOrLower(?string $v, array $mapping, string $fallback): string
    {
        return $mapping[strtolower(trim((string) ($v ?? '')))] ?? $fallback;
    }

    /** Integer decision table: mapping[v] ?? fallback. */
    public static function intMapOr(int $v, array $mapping, string $fallback): string
    {
        return $mapping[$v] ?? $fallback;
    }

    /** ARIA state stringification: always 'true'/'false', never omitted. */
    public static function boolStr(bool $v): string
    {
        return $v ? 'true' : 'false';
    }

    /** Concatenation of trimmed parts (icon Prefix + Name). */
    public static function concatTrim(?string ...$parts): string
    {
        $out = '';
        foreach ($parts as $p) {
            $out .= trim((string) ($p ?? ''));
        }
        return $out;
    }

    /** Returns tag when allowed for the group, otherwise fallback. */
    public static function resolveTag(?string $tag, string $fallback, string $group): string
    {
        $t = strtolower(trim((string) ($tag ?? '')));
        if ($t === '') {
            return $fallback;
        }
        return in_array($t, self::GROUPS[$group] ?? [], true) ? $t : $fallback;
    }

    /** h1–h6 from a heading level; 0/out-of-range default to h2. */
    public static function titleTag(int $order): string
    {
        return match ($order) {
            1 => 'h1',
            3 => 'h3',
            4 => 'h4',
            5 => 'h5',
            6 => 'h6',
            default => 'h2',
        };
    }

    /**
     * Merge recipe base, keyed variant selections, and extra fragments.
     * Recipe shape mirrors *.variants.json: base, keys, defaults, byKey.
     * Unknown choices are silently dropped (Go Compose behavior).
     */
    public static function compose(array $recipe, array $selection, ?string ...$extra): string
    {
        $parts = [];
        $base = trim((string) ($recipe['base'] ?? ''));
        if ($base !== '') {
            $parts[] = $base;
        }
        foreach (($recipe['keys'] ?? []) as $key) {
            $choices = $recipe['byKey'][$key] ?? null;
            if ($choices === null) {
                continue;
            }
            $choice = trim((string) ($selection[$key] ?? ''));
            if ($choice === '') {
                $choice = trim((string) ($recipe['defaults'][$key] ?? ''));
            }
            if ($choice === '') {
                continue;
            }
            $cls = $choices[$choice] ?? null;
            if ($cls !== null && trim($cls) !== '') {
                $parts[] = $cls;
            }
        }
        return self::cn(...$parts, ...$extra);
    }

    /**
     * Print an attribute string for Twig templates. Computed attrs first,
     * rest (caller) attrs merged last so the caller wins. Semantics match
     * the canonical renderer: null/false omit, true prints a bare boolean
     * attribute, everything else stringifies and escapes.
     */
    public static function attrStr(array $attrs, array $rest = []): string
    {
        $merged = array_merge($attrs, $rest);
        $out = '';
        foreach ($merged as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }
            if ($value === true) {
                $out .= ' ' . $name;
                continue;
            }
            $out .= ' ' . $name . '="' . htmlspecialchars((string) $value, ENT_QUOTES) . '"';
        }
        return $out;
    }
}
