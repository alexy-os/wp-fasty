<?php

/**
 * Pure helpers of the N+1 guards in tools/perf: an N+1 is a query shape that runs more often when the page lists
 * more items (one query per post, author, term...), not a different number of batched queries. A fixed repeat (the
 * listed posts, then their featured images: both `wp_posts WHERE ID IN (...)`) is fine. A page whose posts have no
 * author (post_author 0) or no media skips the batched users/attachments query, so raw counts differ with the data.
 */
if (!function_exists('wpfasty_perf_query_shape')) {
    /** SQL with its literals and IN (...) lists folded: the same shape for every post, author or term id. */
    function wpfasty_perf_query_shape(string $sql): string
    {
        $shape = (string) preg_replace("/'(?:''|[^'\\\\]|\\\\.)*'/", '?', $sql);
        $shape = (string) preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $shape);
        $shape = (string) preg_replace('/\bIN\s*\([^)]*\)/i', 'IN (?)', $shape);

        return trim((string) preg_replace('/\s+/', ' ', $shape));
    }

    /**
     * Query shapes run more than once.
     *
     * @param list<string> $sqls
     *
     * @return array<string, int> shape => times
     */
    function wpfasty_perf_repeated_shapes(array $sqls): array
    {
        $times = array_count_values(array_map('wpfasty_perf_query_shape', $sqls));

        return array_filter($times, static fn (int $n): bool => $n > 1);
    }

    /**
     * Shapes that run more often in $large than in $small (the same render with more items or another page) by more
     * than one: one extra run is a batch that only exists when the items need it (the first post with a featured
     * image adds one `wp_posts WHERE ID IN (...)` for all images); a per-item query grows with the items.
     *
     * @param list<string> $small
     * @param list<string> $large
     *
     * @return array<string, array{0: int, 1: int}> shape => [times small, times large]
     */
    function wpfasty_perf_growing_shapes(array $small, array $large): array
    {
        $a = array_count_values(array_map('wpfasty_perf_query_shape', $small));
        $out = [];
        foreach (array_count_values(array_map('wpfasty_perf_query_shape', $large)) as $shape => $n) {
            if ($n > ($a[$shape] ?? 0) + 1) {
                $out[(string) $shape] = [$a[$shape] ?? 0, $n];
            }
        }

        return $out;
    }
}
