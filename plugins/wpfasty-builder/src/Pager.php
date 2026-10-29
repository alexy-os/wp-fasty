<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * The pager of a list block (codegen-x PostGrid `pager`, record PostPager: mode, page, pages, label, summary, previous,
 * next, pageLabel). The WordPress adapter fills the whole group; a layout sets only `mode` (the variant axis
 * `pager.mode`), never the numbers or the texts — like the paths of the current record (richtext).
 *   page   the page of the request: the main query's `paged` (`page` on a static front page), from 1
 *   pages  the block's own query (bound posts: found rows of that query, DataSources::paged) or, without one, the main
 *          query (the theme context `pagination` of ContextFactory, else max_num_pages)
 *   texts  translatable (text domain wpfasty-builder), so the previous / next buttons always have a name
 *   url    link mode (codegen-x 000ee4a6 Pager `pageUrl` / `firstUrl`): on the page (Front) the address of page {0}
 *          and of page 1, get_pagenum_link() of the request (urls(); ContextFactory `pagination.url` / `first_url` on
 *          a main-query route) through Safe::url — the pages and previous / next are <a href>; the editor preview
 *          (Fragment) keeps buttons. A layout never sets them (Layout keeps only `mode`).
 * With pages ≤ 1 there is no pager (the value is null and the template prints nothing). The head also gets
 * <link rel="prev|next"> (links(), esc_url).
 */
final class Pager
{
    /** record types of a pager group in the catalog */
    public const RECORDS = ['PostPager'];
    /** the one field of the group a layout may set, `mode`, and its values (catalog axis `pager.mode`) */
    public const MODES = ['none', 'pages'];

    /** a page number get_pagenum_link() never gets from a request, replaced by the `{0}` of the address template */
    private const SENTINEL = 987654321;

    /** @var array{0: int, 1: int}|null the first pager on the page (page, pages), for the head links */
    private static ?array $head = null;

    /** true when the group at $path of the merged props asks for pages */
    public static function active(array $props, string $path): bool
    {
        $v = $props;
        foreach (explode('.', $path) as $k) {
            $v = is_array($v) ? ($v[$k] ?? null) : null;
        }
        return is_array($v) && ($v['mode'] ?? null) === 'pages';
    }

    /** the page of the request, from the main query: `paged`, on a static front page `page` */
    public static function request(): int
    {
        $paged = (int) get_query_var('paged');
        if ($paged < 1 && function_exists('is_front_page') && is_front_page()) {
            $paged = (int) get_query_var('page');
        }
        return max(1, $paged);
    }

    /**
     * page and pages of the main query: the theme context of ContextFactory (`pagination.current`, `total`) when it
     * has them, else the main WP_Query (no query is run)
     *
     * @param array<string, mixed> $context
     * @return array{0: int, 1: int}
     */
    public static function main(array $context = []): array
    {
        $p = is_array($context['pagination'] ?? null) ? $context['pagination'] : null;
        if ($p !== null && isset($p['current'], $p['total'])) {
            return [max(1, (int) $p['current']), max(1, (int) $p['total'])];
        }
        global $wp_query;
        $pages = isset($wp_query) && is_object($wp_query) ? (int) $wp_query->max_num_pages : 1;
        return [self::request(), max(1, $pages)];
    }

    /**
     * Addresses of the link mode: [address of page {0}, address of page 1] of the request (get_pagenum_link, no
     * query), through Safe::url ("" when refused). The theme context (ContextFactory `pagination.url`, `first_url`)
     * is used when it has them.
     *
     * @param array<string, mixed> $context
     * @return array{url: string, firstUrl: string}
     */
    public static function urls(array $context = []): array
    {
        $p = is_array($context['pagination'] ?? null) ? $context['pagination'] : [];
        $url = is_string($p['url'] ?? null) ? $p['url'] : self::template();
        $first = is_string($p['first_url'] ?? null) ? $p['first_url'] : (string) get_pagenum_link(1, false);
        $url = str_contains($url, '{0}') ? Safe::url(Safe::text($url)) : '';
        return ['url' => $url, 'firstUrl' => Safe::url(Safe::text($first))];
    }

    /** get_pagenum_link() of the request with `{0}` for the page number (no escaping: esc_url would drop the braces) */
    public static function template(): string
    {
        $url = (string) get_pagenum_link(self::SENTINEL, false);
        return str_contains($url, (string) self::SENTINEL) ? str_replace((string) self::SENTINEL, '{0}', $url) : '';
    }

    /**
     * The group the template reads; null when there is a single page (no pager). With $urls (urls()) the link mode.
     *
     * @param array{url: string, firstUrl: string}|null $urls
     * @return array{mode: string, page: int, pages: int, label: string, summary: string, previous: string,
     *   next: string, pageLabel: string, url?: string, firstUrl?: string}|null
     */
    public static function value(int $page, int $pages, ?array $urls = null): ?array
    {
        if ($pages <= 1) {
            return null;
        }
        self::$head ??= [$page, $pages];
        return [
            'mode' => 'pages',
            'page' => max(1, $page),
            'pages' => $pages,
            'label' => __('Pages of posts', 'wpfasty-builder'),
            /* translators: pager summary line: {0} the current page, {1} the number of pages (keep the braces). */
            'summary' => __('Page {0} of {1}', 'wpfasty-builder'),
            'previous' => __('Previous page', 'wpfasty-builder'),
            'next' => __('Next page', 'wpfasty-builder'),
            /* translators: accessible name of a page number button: {0} is the page number (keep the braces). */
            'pageLabel' => __('Page {0}', 'wpfasty-builder'),
        ] + ($urls !== null && str_contains($urls['url'], '{0}')
            ? array_filter($urls, static fn (string $u): bool => $u !== '')
            : []);
    }

    /**
     * Addresses of the pages around $page (get_pagenum_link, escaped with esc_url).
     *
     * @return array{previous: ?string, next: ?string}
     */
    public static function links(int $page, int $pages): array
    {
        return [
            'previous' => $page > 1 && $page <= $pages ? esc_url(get_pagenum_link($page - 1, false)) : null,
            'next' => $page < $pages ? esc_url(get_pagenum_link($page + 1, false)) : null,
        ];
    }

    /** <link rel="prev|next"> of the first pager of the page (wp_head) */
    public static function head(): void
    {
        if (self::$head === null) {
            return;
        }
        foreach (self::links(...self::$head) as $rel => $url) {
            if ($url !== null && $url !== '') {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $url is esc_url()'d in links()
                printf("<link rel=\"%s\" href=\"%s\" />\n", $rel === 'previous' ? 'prev' : 'next', $url);
            }
        }
    }

    public static function reset(): void
    {
        self::$head = null;
    }
}
