<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Model\Post;

/**
 * The template context filters, typed: `wpfasty_post_data` (one post view-model; receives the Core Post DTO) and
 * `wpfasty_context` (the whole context; receives the queried WP_Post or null). A callback that returns a non-array
 * is ignored (the unfiltered value is kept) instead of breaking the template.
 */
final class ContextFilters
{
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function postData(array $data, Post $post): array
    {
        return self::map(apply_filters('wpfasty_post_data', $data, $post, $post->singular), $data);
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public function context(array $context, ?\WP_Post $post): array
    {
        return self::map(apply_filters('wpfasty_context', $context, $post), $context);
    }

    /**
     * @param array<string, mixed> $fallback
     *
     * @return array<string, mixed>
     */
    private static function map(mixed $filtered, array $fallback): array
    {
        if (!\is_array($filtered)) {
            return $fallback;
        }
        $out = [];
        foreach ($filtered as $key => $value) {
            $out[(string) $key] = $value;
        }

        return $out;
    }
}
