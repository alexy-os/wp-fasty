<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use RuntimeException;
use WPFastyBuilder\Source\LocalSource;
use WPFastyBuilder\Source\RemoteSource;
use WPFastyBuilder\Source\SourceInterface;

/**
 * The block sources of the site, all of the site engine's runtime (Plugin::engine()): the bundled ui8kit source of
 * that runtime (sources/ui8kit/<runtime>, present only in a plugin build of that engine) and the remote registries an
 * administrator imported (option wpfasty_builder_sources: [{id, url, runtime, version, imported_at}], files in
 * uploads/wpfasty-builder/sources/<id>). A source of the other runtime is never offered (ADR-001, K7).
 */
final class Sources
{
    public const OPTION = 'wpfasty_builder_sources';
    public const MISSING = 'wpfasty_builder_missing';

    /** @var array<string, SourceInterface>|null */
    private static ?array $all = null;
    /** @var array<string, Catalog> */
    private static array $catalogs = [];
    private static ?string $engine = null;

    /** @return array<string, SourceInterface> */
    public static function all(): array
    {
        $engine = Plugin::engine();
        if (self::$all !== null && self::$engine === $engine) {
            return self::$all;
        }
        self::$engine = $engine;
        self::$catalogs = [];
        if ($engine === null) {
            return self::$all = [];
        }
        $all = [];
        if (Plugin::ships($engine)) {
            $all['ui8kit'] = new LocalSource('ui8kit', Plugin::$dir . '/sources/ui8kit/' . $engine, $engine);
        }
        foreach ((array) get_option(self::OPTION, []) as $row) {
            $valid = is_array($row) && is_string($row['id'] ?? null) && is_string($row['url'] ?? null);
            // rows without a runtime were imported before K7; their stored copy tells which runtimes it holds
            $own = $valid && in_array($row['runtime'] ?? $engine, [$engine], true);
            if ($own && !isset($all[$row['id']])) {
                $remote = self::remote($row['id'], $row['url'], $engine);
                if ($remote->imported()) {
                    $all[$row['id']] = $remote;
                }
            }
        }
        return self::$all = (array) apply_filters('wpfasty_builder_sources', $all);
    }

    public static function get(string $id): ?SourceInterface
    {
        return self::all()[$id] ?? null;
    }

    public static function catalog(string $id): ?Catalog
    {
        $src = self::get($id);
        if ($src === null) {
            return null;
        }
        return self::$catalogs[$id] ??= new Catalog($src->catalog());
    }

    public static function remote(string $id, string $url, string $runtime): RemoteSource
    {
        return new RemoteSource($id, $url, Store::dir() . '/sources/' . $id, $runtime, [self::class, 'fetch']);
    }

    /**
     * What keeps blocks of this site from rendering (shown as an admin notice): no engine, a plugin build without the
     * site runtime, imported registries of another runtime, blocks missing from the site runtime's sources.
     *
     * @return list<string>
     */
    public static function problems(): array
    {
        $engine = Plugin::engine();
        if ($engine === null) {
            return [__(
                'WP FastY Builder: the active theme has no WP FastY renderer; landings do not render.',
                'wpfasty-builder',
            )];
        }
        $out = [];
        if (!Plugin::ships($engine)) {
            $out[] = sprintf(
                // phpcs:disable Generic.Files.LineLength.TooLong -- a translatable literal must stay one string.
                /* translators: 1: site engine (latte or twig), 2: engines of this plugin build */
                __('WP FastY Builder: this site renders %1$s, but this plugin build ships %2$s blocks only. Install the %1$s build of the plugin.', 'wpfasty-builder'),
                // phpcs:enable Generic.Files.LineLength.TooLong
                $engine,
                implode(', ', Plugin::runtimes()) ?: '-',
            );
        }
        foreach ((array) get_option(self::OPTION, []) as $row) {
            if (!is_array($row) || !is_string($row['id'] ?? null) || !is_string($row['url'] ?? null)) {
                continue;
            }
            $runtime = (string) ($row['runtime'] ?? $engine);
            if ($runtime !== $engine || !self::remote($row['id'], $row['url'], $engine)->imported()) {
                $out[] = sprintf(
                    // phpcs:disable Generic.Files.LineLength.TooLong -- a translatable literal must stay one string.
                    /* translators: 1: source id, 2: its runtime, 3: site engine */
                    __('WP FastY Builder: source %1$s holds %2$s blocks; this site renders %3$s. Import the %3$s registry of this source.', 'wpfasty-builder'),
                    // phpcs:enable Generic.Files.LineLength.TooLong
                    $row['id'],
                    $runtime,
                    $engine,
                );
            }
        }
        $missing = (array) get_option(self::MISSING, []);
        if ($missing !== []) {
            $out[] = sprintf(
                /* translators: 1: site engine, 2: list of source:template */
                __('WP FastY Builder: blocks missing from the %1$s sources render nothing: %2$s', 'wpfasty-builder'),
                $engine,
                implode(', ', array_map('strval', array_keys($missing))),
            );
        }
        return $out;
    }

    /** records a block that could not render in the site runtime (bounded list, for problems()) */
    public static function recordMissing(string $source, string $template): void
    {
        $missing = (array) get_option(self::MISSING, []);
        $key = $source . ':' . $template;
        if (isset($missing[$key]) || count($missing) >= 20) {
            return;
        }
        $missing[$key] = gmdate('c');
        update_option(self::MISSING, $missing, false);
    }

    /**
     * Adds or refreshes a remote source (administrators only; the caller checks the capability and nonce).
     *
     * @return array{blocks: int, collections: int, runtime: string, version: string}
     */
    public static function import(string $id, string $url): array
    {
        $engine = Plugin::engine();
        if ($engine === null) {
            throw new RuntimeException(__('The active theme has no WP FastY renderer.', 'wpfasty-builder'));
        }
        if (preg_match(Catalog::IDENT, $id) !== 1 || $id === 'ui8kit') {
            throw new RuntimeException(__(
                'The source id must be a short lowercase name other than ui8kit.',
                'wpfasty-builder',
            ));
        }
        $url = esc_url_raw($url, ['https', 'http']);
        if ($url === '' || !wp_http_validate_url($url)) {
            throw new RuntimeException(__('The source URL is not a valid public http(s) URL.', 'wpfasty-builder'));
        }
        $stats = self::remote($id, $url, $engine)->import();
        $rows = array_values(array_filter(
            (array) get_option(self::OPTION, []),
            static fn ($r): bool => is_array($r) && ($r['id'] ?? '') !== $id,
        ));
        $rows[] = [
            'id' => $id,
            'url' => $url,
            'runtime' => $stats['runtime'],
            'version' => $stats['version'],
            'imported_at' => gmdate('c'),
        ];
        delete_option(self::MISSING);
        update_option(self::OPTION, $rows, false);
        self::$all = null;
        unset(self::$catalogs[$id]);
        return $stats;
    }

    public static function fetch(string $url): string
    {
        $res = wp_safe_remote_get($url, ['timeout' => 30, 'limit_response_size' => 20 * 1024 * 1024]);
        if (is_wp_error($res)) {
            throw new RuntimeException($res->get_error_message());
        }
        $code = (int) wp_remote_retrieve_response_code($res);
        if ($code !== 200) {
            throw new RuntimeException(sprintf('%s: HTTP %d', $url, $code));
        }
        return (string) wp_remote_retrieve_body($res);
    }
}
