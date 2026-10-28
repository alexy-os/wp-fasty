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
 * With pages ≤ 1 there is no pager (the value is null and the template prints nothing). Links of the pages are
 * get_pagenum_link() through esc_url (links(), printed as <link rel="prev|next"> in the head: the codegen-x Pager has
 * buttons without an address).
 */
final class Pager
{
    /** record types of a pager group in the catalog */
    public const RECORDS = ['PostPager'];
    /** the one field of the group a layout may set, `mode`, and its values (catalog axis `pager.mode`) */
    public const MODES = ['none', 'pages'];

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
     * The group the template reads; null when there is a single page (no pager).
     *
     * @return array{mode: string, page: int, pages: int, label: string, summary: string, previous: string,
     *   next: string, pageLabel: string}|null
     */
    public static function value(int $page, int $pages): ?array
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
        ];
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
