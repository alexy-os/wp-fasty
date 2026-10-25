<?php

declare(strict_types=1);

namespace WPFastyTypes\Codex;

/**
 * The active theme's Codex description: types/codex.manifest.json (neutral, digested) and types/codex.delivery.json
 * (taxonomy roles + WordPress storage of every field). Child theme first, then the parent.
 */
final class Site
{
    /** Platform section of codex.delivery.json (Q3 `wordpress`, shortened: Theme Check rejects the lowercase word). */
    public const WP = 'wp';

    private static ?self $current = null;

    /**
     * @param array<string, mixed> $manifest   decoded with JSON objects as arrays (read-only use)
     * @param array<string, mixed> $delivery
     */
    public function __construct(
        public readonly array $manifest,
        public readonly array $delivery,
        public readonly string $digest,
    ) {
    }

    public static function current(): ?self
    {
        if (self::$current !== null) {
            return self::$current;
        }
        $dirs = function_exists('get_stylesheet_directory')
            ? array_unique([get_stylesheet_directory(), get_template_directory()])
            : [];
        foreach ($dirs as $dir) {
            $site = self::fromDir((string) $dir . '/types');
            if ($site !== null) {
                return self::$current = $site;
            }
        }
        // a theme without its own Codex manifest: core post / page (generated, see theme-builder coreCodex())
        return self::$current = self::fromDir(dirname(__DIR__, 2) . '/codex');
    }

    public static function reset(?self $site = null): void
    {
        self::$current = $site;
    }

    public static function fromDir(string $dir): ?self
    {
        $m = $dir . '/codex.manifest.json';
        $d = $dir . '/codex.delivery.json';
        if (!is_file($m)) {
            return null;
        }
        $text = (string) file_get_contents($m);
        $digest = Canonical::manifestDigest(Json::parse($text));
        $manifest = (array) json_decode($text, true);
        $delivery = is_file($d) ? (array) json_decode((string) file_get_contents($d), true) : [];
        return new self($manifest, $delivery, $digest);
    }

    /** @return array<string, mixed>|null resource {record, taxonomies?} of a kind */
    public function resource(string $kind): ?array
    {
        foreach ((array) ($this->manifest['resources'] ?? []) as $r) {
            if (($r['record']['id'] ?? null) === $kind) {
                return $r;
            }
        }
        return null;
    }

    /** @return list<string> entry kinds stored as posts (manifest resources minus media / user) */
    public function postKinds(): array
    {
        $out = [];
        foreach ((array) ($this->manifest['resources'] ?? []) as $r) {
            $id = (string) ($r['record']['id'] ?? '');
            if ($id !== '' && $id !== 'media' && $id !== 'user') {
                $out[] = $id;
            }
        }
        return $out;
    }

    /** Codex kind stored as this WP post type (delivery wp.kinds.*.post_type), null when the site does not declare one. */
    public function kindOf(string $postType): ?string
    {
        foreach ($this->postKinds() as $kind) {
            if ((string) ($this->wp($kind)['post_type'] ?? $kind) === $postType) {
                return $kind;
            }
        }
        return null;
    }

    /** @return array<string, array<string, mixed>> field id → manifest field */
    public function fields(string $kind): array
    {
        $out = [];
        foreach ((array) ($this->resource($kind)['record']['fields'] ?? []) as $f) {
            $out[(string) $f['id']] = (array) $f;
        }
        return $out;
    }

    /** @return list<string> */
    public function taxonomies(string $kind): array
    {
        return array_values(array_map('strval', (array) ($this->resource($kind)['taxonomies'] ?? [])));
    }

    /** @return list<string> every taxonomy of the manifest */
    public function allTaxonomies(): array
    {
        $out = [];
        foreach ((array) ($this->manifest['resources'] ?? []) as $r) {
            foreach ((array) ($r['taxonomies'] ?? []) as $t) {
                $out[(string) $t] = true;
            }
        }
        $out = array_keys($out);
        sort($out, SORT_STRING);
        return $out;
    }

    /** Taxonomy with a role (category / tag) among the taxonomies of a kind; '' when none. */
    public function roleTaxonomy(string $kind, string $role): string
    {
        $roles = [];
        foreach ((array) ($this->delivery['taxonomies'] ?? []) as $t) {
            $roles[(string) $t['id']] = (string) ($t['role'] ?? '');
        }
        foreach ($this->taxonomies($kind) as $t) {
            if (($roles[$t] ?? '') === $role) {
                return $t;
            }
        }
        return '';
    }

    /** @return array<string, mixed> WordPress delivery of a kind: post_type, meta (field → meta key), purchasable */
    public function wp(string $kind): array
    {
        return (array) ($this->delivery[self::WP]['kinds'][$kind] ?? ['post_type' => $kind]);
    }

    public function currencyOption(): string
    {
        return (string) ($this->delivery[self::WP]['settings']['currency'] ?? 'wpfasty_currency');
    }
}
