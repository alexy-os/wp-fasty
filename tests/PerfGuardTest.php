<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\TestCase;

require_once \dirname(__DIR__).'/tools/perf/guard.php';

final class PerfGuardTest extends TestCase
{
    public function testShapeFoldsIdsStringsAndInLists(): void
    {
        self::assertSame(
            'SELECT * FROM wp_users WHERE ID IN (?) AND user_login = ?',
            wpfasty_perf_query_shape("SELECT *  FROM wp_users\n WHERE ID IN (1, 7) AND user_login = 'it''s'")
        );
    }

    public function testOneQueryPerAuthorIsAnNPlusOne(): void
    {
        $repeated = wpfasty_perf_repeated_shapes([
            'SELECT * FROM wp_posts WHERE ID IN (3,4,5)',
            'SELECT * FROM wp_users WHERE ID = 1',
            'SELECT * FROM wp_users WHERE ID = 2',
        ]);
        self::assertSame(['SELECT * FROM wp_users WHERE ID = ?' => 2], $repeated);
    }

    /** The local false positive: page 2 has only author-less posts, so the batched users query is skipped. */
    public function testFewerBatchedQueriesAreNotAnNPlusOne(): void
    {
        $page1 = [
            'SELECT t.term_id FROM wp_terms WHERE t.term_id IN (1)',
            'SELECT wp_users.ID FROM wp_users WHERE wp_users.ID IN (1)',
            'SELECT user_id, meta_key FROM wp_usermeta WHERE user_id IN (1)',
        ];
        self::assertSame([], wpfasty_perf_repeated_shapes($page1));
        self::assertSame([], wpfasty_perf_repeated_shapes([$page1[0]]));
    }
}
