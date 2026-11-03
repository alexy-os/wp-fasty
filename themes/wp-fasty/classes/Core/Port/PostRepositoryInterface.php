<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

use WPFasty\Core\Model\Post;

/** Entries for the views; a list is loaded with one query per kind of data (no N+1). */
interface PostRepositoryInterface
{
    /** One entry; $singular adds link pages and the previous/next post. */
    public function find(int $id, bool $singular = false): ?Post;

    /**
     * Published posts, newest first (front page "latest posts").
     *
     * @return list<Post>
     */
    public function recent(int $count): array;

    /**
     * The posts of the current main query (archive, search, home).
     *
     * @return list<Post>
     */
    public function mainQuery(): array;
}
