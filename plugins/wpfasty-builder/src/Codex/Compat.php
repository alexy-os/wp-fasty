<?php

declare(strict_types=1);

namespace WPFastyBuilder\Codex;

/**
 * Block slot ↔ Codex type compatibility (DESIGN.md D): exact < auto (closed rule set R1–R9) < manual.
 * Pure: sources come from the Codex manifest + delivery (Sources::fromManifest); slots are
 * {kind, required: [paths]} (codegen-x blocks.json) or catalog record fields (paths with pipes).
 * resolve() returns the level and the effective readPath for the source, so the builder reads exactly what was rated.
 */
final class Compat
{
    public const EXACT = 'exact';
    public const AUTO = 'auto';
    public const MANUAL = 'manual';
    public const RANK = [self::EXACT => 0, self::AUTO => 1, self::MANUAL => 2];

    /** Q6: presentational fields are optional and take a default at binding time. */
    public const PRESENTATIONAL = ['icon', 'size', 'tone', 'hint', 'mark'];
    /** Defaults per source kind ("*" = any); term sources use their role. */
    public const DEFAULTS = [
        'icon' => [
            'post' => 'file-text', 'page' => 'file', 'project' => 'briefcase', 'product' => 'shopping-bag',
            'category' => 'folder', 'tag' => 'tag', '*' => 'file-text',
        ],
        'size' => ['*' => 'small'],
    ];
    /** Chrome keys a readPath may address directly (codex-entry/v1 and codex-term/v1). */
    private const CHROME = ['id', 'title', 'name', 'slug', 'content', 'excerpt', 'description', 'author_id',
        'featured_media_id', 'parent_id', 'published_at', 'updated_at', 'created_at', 'order', 'locale'];
    private const STRINGY = ['string', 'text', 'textarea', 'richtext', 'markdown', 'select'];

    /**
     * @param array<string, mixed>        $src  Sources::fromManifest item
     * @param array{0:string,1:list<string>}|null $need required field type of the slot, if known
     * @param string|null                 $wantTax taxonomy the slot expects for bare "terms"
     * @return array{level:string, rule:string, path:?string, default?:mixed}
     */
    public static function resolve(string $path, array $src, ?array $need = null, ?string $wantTax = null): array
    {
        $bar = strpos($path, '|');
        $base = $bar === false ? $path : substr($path, 0, $bar);
        $pipe = $bar === false ? '' : substr($path, $bar);
        $term = (bool) $src['term'];
        $chrome = $src['chrome'];
        $exact = ['level' => self::EXACT, 'rule' => '', 'path' => $path];
        $manual = static fn (string $why = ''): array => ['level' => self::MANUAL, 'rule' => $why, 'path' => null];
        $auto = static fn (string $rule, string $to): array => ['level' => self::AUTO, 'rule' => $rule, 'path' => $to];

        if ($base === 'id') {
            return $exact;
        }
        if ($base === 'title' || $base === 'name') {
            if ($term === ($base === 'name')) {
                return $exact;
            }
            return $term ? $auto('R9 title<-name', 'name' . $pipe) : $manual();
        }
        if ($base === 'terms') {
            return self::terms($pipe, $src, $wantTax);
        }
        if (in_array($base, self::CHROME, true)) {
            return in_array($base, $chrome, true) ? $exact : $manual();
        }
        if (preg_match('/^metadata\.([A-Za-z0-9_]+)\.value$/', $base, $m) !== 1) {
            return $manual('unknown path');
        }
        $f = $m[1];
        if (isset($src['fields'][$f])) {
            return self::typeOk($need, $src['fields'][$f]) ? $exact : $manual('type ' . $src['fields'][$f][0]);
        }
        // closed auto rule set R (D.3)
        $cat = self::taxOfRole($src, 'category');
        $tag = self::taxOfRole($src, 'tag') ?? $cat;
        $text = in_array('excerpt', $chrome, true) || in_array('content', $chrome, true);
        if (($f === 'summary' || $f === 'text') && $text) {
            return $auto('R1 excerpt|excerpt-fallback', 'excerpt|excerpt-fallback');
        }
        if ($f === 'category' && $cat !== null) {
            return $auto('R2 terms|term:' . $cat, 'terms|term:' . $cat);
        }
        if ($f === 'date' && in_array('published_at', $chrome, true)) {
            return $auto('R3 published_at|date', 'published_at' . ($pipe === '' ? '|date' : $pipe));
        }
        if ($f === 'minutes' && in_array('content', $chrome, true)) {
            return $auto('R4 content|reading-minutes', 'content|reading-minutes');
        }
        if ($f === 'currency' && self::hasRule($src, 'money')) {
            return $auto('R5 settings:currency', 'metadata.currency.value|settings:currency');
        }
        if ($f === 'inStock' && $src['stock'] !== null) {
            return $auto('R6 in-stock', 'metadata.' . $src['stock'] . '.value|in-stock');
        }
        if ($f === 'tags' && $tag !== null) {
            return $auto('R7 terms|term-names:' . $tag, 'terms|term-names:' . $tag);
        }
        if ($f === 'tag' && $tag !== null) {
            return $auto('R7 terms|term:' . $tag, 'terms|term:' . $tag);
        }
        if ($f === 'count' && $term) {
            return $auto('R8 metadata.count', 'metadata.count.value');
        }
        if ($f === 'author' && in_array('author_id', $chrome, true)) {
            return $auto('R3 author_id|author', 'author_id|author');
        }
        if (in_array($f, self::PRESENTATIONAL, true)) {
            $d = self::presentationalDefault($f, $src);
            $out = ['level' => self::MANUAL, 'rule' => 'presentational', 'path' => null];
            return $d === null ? $out : $out + ['default' => $d];
        }
        return $manual('missing');
    }

    /** @param array<string, mixed> $src */
    public static function presentationalDefault(string $field, array $src): mixed
    {
        $table = self::DEFAULTS[$field] ?? null;
        if ($table === null) {
            return null;
        }
        $key = $src['term'] ? ($src['role'] ?? 'category') : $src['kind'];
        return $table[$key] ?? $table['*'] ?? null;
    }

    /**
     * Slot level over its required paths. $relax: presentational fields do not count (Q6).
     *
     * @param list<string>          $required
     * @param array<string, mixed>  $src
     * @param array<string, array{0:string,1:list<string>}> $needs path → field type
     * @return array{level:string, notes:list<string>}
     */
    public static function slot(
        array $required,
        array $src,
        array $needs = [],
        ?string $wantTax = null,
        bool $relax = true,
    ): array {
        $worst = self::EXACT;
        $notes = [];
        foreach ($required === [] ? ['id', 'title'] : $required as $p) {
            if ($relax && preg_match('/^metadata\.(' . implode('|', self::PRESENTATIONAL) . ')\.value/', $p) === 1) {
                continue;
            }
            $r = self::resolve($p, $src, $needs[$p] ?? null, $wantTax);
            if (self::RANK[$r['level']] > self::RANK[$worst]) {
                $worst = $r['level'];
            }
            if ($r['level'] !== self::EXACT) {
                $notes[] = $p . ':' . $r['level'] . ($r['rule'] !== '' ? '(' . $r['rule'] . ')' : '');
            }
        }
        return ['level' => $worst, 'notes' => $notes];
    }

    /** @param array<string, mixed> $src */
    private static function terms(string $pipe, array $src, ?string $wantTax): array
    {
        $taxes = $src['taxes'];
        if ($taxes === []) {
            return ['level' => self::MANUAL, 'rule' => 'no taxonomy', 'path' => null];
        }
        if (preg_match('/^\|(term|term-slug|term-names):(.+)$/', $pipe, $m) === 1) {
            $wantTax = $m[2];
        }
        if ($wantTax === null || in_array($wantTax, $taxes, true)) {
            return ['level' => self::EXACT, 'rule' => '', 'path' => 'terms' . $pipe];
        }
        $role = $src['roleOf'][$wantTax] ?? (str_contains($wantTax, 'tag') ? 'tag' : 'category');
        $have = self::taxOfRole($src, $role) ?? ($role === 'tag' ? null : $taxes[0]);
        if ($have === null) {
            return ['level' => self::MANUAL, 'rule' => 'no ' . $role . ' taxonomy', 'path' => null];
        }
        $to = $pipe === '' ? 'terms' : preg_replace('/:(.+)$/', ':' . $have, $pipe);
        $rule = "R2 taxonomy role {$role}: {$wantTax}<-{$have}";
        return ['level' => self::AUTO, 'rule' => $rule, 'path' => $pipe === '' ? 'terms' : 'terms' . $to];
    }

    /** @param array<string, mixed> $src */
    private static function taxOfRole(array $src, string $role): ?string
    {
        foreach ($src['taxes'] as $t) {
            if (($src['roleOf'][$t] ?? null) === $role) {
                return $t;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $src */
    private static function hasRule(array $src, string $rule): bool
    {
        foreach ($src['fields'] as $f) {
            if (in_array($rule, $f[1], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array{0:string,1:list<string>}|null $need
     * @param array{0:string,1:list<string>}      $have
     */
    public static function typeOk(?array $need, array $have): bool
    {
        if ($need === null) {
            return true;
        }
        [$nt, $nr] = $need;
        [$ht, $hr] = $have;
        if (in_array($nt, self::STRINGY, true) && in_array($ht, self::STRINGY, true)) {
            return true;
        }
        if ($nt !== $ht) {
            return false;
        }
        if (in_array('money', $nr, true) && !in_array('money', $hr, true)) {
            return false;
        }
        return !in_array('integer', $nr, true) || array_intersect(['integer', 'money'], $hr) !== [];
    }
}
