<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Model\Post;
use WPFasty\Core\Port\PostRepositoryInterface;

/** The WordPress side of the post port: callers that already hold WP_Post objects (the theme facade, api.php). */
interface WordPressPostRepositoryInterface extends PostRepositoryInterface
{
    /** Maps a loaded post (a list should be prime()d first). */
    public function fromWordPress(\WP_Post $post, bool $singular = false): Post;

    /**
     * Batch-loads everything a list of posts touches (ADR-001 §4): post meta, taxonomy terms, featured image posts +
     * meta, authors. One query per kind instead of N+1.
     *
     * @param list<\WP_Post> $posts
     */
    public function prime(array $posts): void;
}
