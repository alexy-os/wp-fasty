<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use WPFasty\Data\ContextFactory;

/**
 * recentPosts() (front page "latest posts") batch-loads meta, terms, featured images and authors for the whole
 * list (ContextFactory::prime) instead of one lookup per post (N+1).
 */
final class ContextFactoryRecentPostsTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testRecentPostsPrimesCachesOncePerKind(): void
    {
        require __DIR__.'/fixtures/recent-posts-stubs.php';

        $posts = [];
        foreach ([101, 102, 103] as $i => $id) {
            $posts[] = new \WP_Post([
                'ID' => $id,
                'post_title' => 'Post '.$id,
                'post_name' => 'post-'.$id,
                'post_author' => 2 === $i ? 7 : 5,
            ]);
        }
        $GLOBALS['wpfasty_recent'] = ['posts' => $posts, 'thumbs' => [101 => 901, 103 => 903], 'log' => []];

        $recent = (new \ReflectionMethod(ContextFactory::class, 'recentPosts'))->invoke(new ContextFactory(), 6);

        $this->assertSame([101, 102, 103], array_column($recent, 'id'));

        $log = $GLOBALS['wpfasty_recent']['log'];
        $calls = array_count_values(array_column($log, 0));
        $this->assertSame(1, $calls['WP_Query']);
        $this->assertSame(1, $calls['update_meta_cache']);
        $this->assertSame(1, $calls['update_object_term_cache']);
        $this->assertSame(1, $calls['_prime_post_caches']);
        $this->assertSame(1, $calls['cache_users']);

        $byFn = array_column($log, 1, 0);
        // WP_Query itself must not run its own per-query meta/term priming on top of prime()
        $this->assertFalse($byFn['WP_Query']['update_post_meta_cache']);
        $this->assertFalse($byFn['WP_Query']['update_post_term_cache']);
        $this->assertSame(['post', [101, 102, 103]], $byFn['update_meta_cache']);
        $this->assertSame([[101, 102, 103], 'post'], $byFn['update_object_term_cache']);
        $this->assertSame([[901, 903], false, true], $byFn['_prime_post_caches']);
        $this->assertSame([[5, 7]], $byFn['cache_users']);
    }

    #[RunInSeparateProcess]
    public function testRecentPostsWithoutPostsRunsNoPriming(): void
    {
        require __DIR__.'/fixtures/recent-posts-stubs.php';
        $GLOBALS['wpfasty_recent'] = ['posts' => [], 'thumbs' => [], 'log' => []];

        $recent = (new \ReflectionMethod(ContextFactory::class, 'recentPosts'))->invoke(new ContextFactory(), 6);

        $this->assertSame([], $recent);
        $this->assertSame(['WP_Query'], array_column($GLOBALS['wpfasty_recent']['log'], 0));
    }
}
