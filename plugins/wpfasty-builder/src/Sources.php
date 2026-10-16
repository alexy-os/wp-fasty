<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use RuntimeException;
use WPFastyBuilder\Source\LocalSource;
use WPFastyBuilder\Source\RemoteSource;
use WPFastyBuilder\Source\SourceInterface;

/**
 * The block sources of the site: the bundled ui8kit source (sources/ui8kit) and the remote ones an administrator
 * imported (option wpfasty_builder_sources: [{id, url}], files in uploads/wpfasty-builder/sources/<id>).
 */
final class Sources
{
    public const OPTION = 'wpfasty_builder_sources';

    /** @var array<string, SourceInterface>|null */
    private static ?array $all = null;
    /** @var array<string, Catalog> */
    private static array $catalogs = [];

    /** @return array<string, SourceInterface> */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }
        $all = ['ui8kit' => new LocalSource('ui8kit', Plugin::$dir . '/sources/ui8kit')];
        foreach ((array) get_option(self::OPTION, []) as $row) {
            $valid = is_array($row) && is_string($row['id'] ?? null) && is_string($row['url'] ?? null);
            if ($valid && !isset($all[$row['id']])) {
                $remote = self::remote($row['id'], $row['url']);
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

    public static function remote(string $id, string $url): RemoteSource
    {
        return new RemoteSource($id, $url, Store::dir() . '/sources/' . $id, [self::class, 'fetch']);
    }

    /**
     * Adds or refreshes a remote source (administrators only; the caller checks the capability and nonce).
     *
     * @return array{blocks: int, collections: int}
     */
    public static function import(string $id, string $url): array
    {
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
        $stats = self::remote($id, $url)->import();
        $rows = array_values(array_filter(
            (array) get_option(self::OPTION, []),
            static fn ($r): bool => is_array($r) && ($r['id'] ?? '') !== $id,
        ));
        $rows[] = ['id' => $id, 'url' => $url, 'imported_at' => gmdate('c')];
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
