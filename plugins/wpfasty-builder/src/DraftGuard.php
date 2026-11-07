<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** Cross-request lock and optimistic revision check shared by visual and agent draft saves. */
final class DraftGuard
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T|\WP_Error
     */
    public static function locked(int $page, callable $operation): mixed
    {
        $key = 'wpfb_draft_lock_'.$page;
        // Fail closed after a process crash. Do not expire a lock while a write may still run.
        if (!add_option($key, time(), '', false)) {
            return new \WP_Error('wpfb_busy', 'Another draft save is active; retry after it completes.', ['status' => 409]);
        }
        try {
            return $operation();
        } finally {
            delete_option($key);
        }
    }

    public static function version(int $page): int
    {
        return Cast::int(Store::get($page, true)['version'] ?? 0);
    }

    /**
     * @param array<string, mixed> $layout
     *
     * @return array{0: array<string, mixed>, 1: list<array{code: string, path: string, message: string}>}|\WP_Error
     */
    public static function save(int $page, array $layout, bool $publish, ?int $expected): mixed
    {
        return self::locked($page, static function () use ($page, $layout, $publish, $expected) {
            if (null !== $expected && self::version($page) !== $expected) {
                return new \WP_Error('wpfb_revision_conflict', 'Draft changed. Reload before saving.', ['status' => 409]);
            }

            return Store::save($page, $layout, $publish);
        });
    }
}
