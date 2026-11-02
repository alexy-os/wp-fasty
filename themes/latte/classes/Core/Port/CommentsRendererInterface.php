<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

use WPFasty\Core\Html\TrustedHtml;

/**
 * Comment list + form of a post as core-generated (core-escaped) HTML; empty unless a theme opts in via the
 * `wpfasty_comments_enabled` filter.
 */
interface CommentsRendererInterface
{
    public function render(int $postId): TrustedHtml;
}
