<?php

/**
 * Pure helpers of the N+1 guards in tools/perf: an N+1 is the same query shape run more than once for one render
 * (one query per post, author, term...), not a different number of batched queries. A page whose posts have no
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
}
