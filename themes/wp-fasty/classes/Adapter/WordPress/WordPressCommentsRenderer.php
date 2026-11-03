<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Port\CommentsRendererInterface;

final class WordPressCommentsRenderer implements CommentsRendererInterface
{
    /**
     * Comment list + form as WP-generated (core-escaped) HTML.
     * Empty unless a theme opts in via the `wpfasty_comments_enabled` filter.
     */
    public function render(int $postId): TrustedHtml
    {
        if (!apply_filters('wpfasty_comments_enabled', false) || !function_exists('wp_list_comments')) {
            return TrustedHtml::empty();
        }
        if (!comments_open($postId) && 0 === (int) get_comments_number($postId)) {
            return TrustedHtml::empty();
        }
        if (post_password_required($postId)) {
            return TrustedHtml::empty(); // comments of a protected post stay behind its password (core comments_template())
        }

        $list = wp_list_comments([
            'style' => 'ol',
            'short_ping' => true,
            'avatar_size' => 40,
            'echo' => false,
        ], get_comments(['post_id' => $postId, 'status' => 'approve', 'order' => 'ASC']));

        ob_start();
        comment_form([
            'class_submit' => 'inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium '
                .'text-primary-foreground',
            'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title mb-4 text-xl font-semibold">',
            'title_reply_after' => '</h3>',
        ], $postId);
        $form = (string) ob_get_clean();

        $count = (int) get_comments_number($postId);
        $heading = $count > 0
            ? '<h2 class="mb-6 text-2xl font-semibold">'.esc_html(sprintf(
                /* translators: %d: number of comments */
                _n('%d comment', '%d comments', $count, 'wp-fasty'),
                $count
            )).'</h2>'
            : '';

        return new TrustedHtml('<section id="comments" class="comments-area">'.$heading
            .($list ? '<ol class="comment-list mb-8 flex flex-col gap-6">'.$list.'</ol>' : '')
            .$form.'</section>');
    }
}
