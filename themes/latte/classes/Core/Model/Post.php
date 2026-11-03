<?php

declare(strict_types=1);

namespace WPFasty\Core\Model;

use WPFasty\Core\Html\TrustedHtml;

/**
 * An entry as the theme renders it. Names follow the Codex v0.4 entry (ui8kit/contract, WPFastyTypes\Codex\Mapper)
 * where they match: id, kind (post type), slug, title, content, excerpt; plus what a view needs on top (url, images,
 * terms, author, neighbours). Text is plain (entities decoded); HTML is TrustedHtml. No WordPress types.
 */
final class Post
{
    /**
     * @param list<Term> $categories
     * @param list<Term> $tags
     */
    public function __construct(
        public readonly int $id,
        /** post type */
        public readonly string $kind,
        public readonly string $slug,
        public readonly string $title,
        /** the_content (or the password form of a protected post) */
        public readonly TrustedHtml $content,
        public readonly string $excerpt,
        public readonly string $url,
        /** publish date: localized display string and ISO 8601 */
        public readonly string $dateDisplay,
        public readonly string $dateIso,
        public readonly ?Image $featuredImage = null,
        public readonly ?Image $thumbnail = null,
        public readonly array $categories = [],
        public readonly array $tags = [],
        public readonly ?Author $author = null,
        /** get_post_class() */
        public readonly string $cssClass = '',
        /** @deprecated since view/v1 `pages`; always empty, kept for child templates. Removed in the next major. */
        public readonly ?TrustedHtml $linkPages = null,
        public readonly ?PostLink $previous = null,
        public readonly ?PostLink $next = null,
        public readonly string $mimeType = '',
        /** the file URL of an attachment, null otherwise */
        public readonly ?string $attachmentUrl = null,
        /** built for a single view (link pages, neighbours) */
        public readonly bool $singular = false,
        /** pages of a multi-page single view (`<!--nextpage-->`), null when it has one page */
        public readonly ?PostPages $pages = null,
    ) {
    }

    public function isAttachment(): bool
    {
        return 'attachment' === $this->kind;
    }
}
