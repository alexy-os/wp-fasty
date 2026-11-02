<?php

declare(strict_types=1);

namespace WPFasty\Data\Post;

use WPFasty\Core\Model\Image;
use WPFasty\Core\Model\Post;
use WPFasty\Core\Model\PostLink;
use WPFasty\Core\Model\Term;

/** Post DTO -> the `page` / `posts[]` view-model of the templates (keys and order are the template contract). */
final class PostViewModelBuilder
{
    /** @return array<string, mixed> */
    public function build(Post $post): array
    {
        $data = [
            'id' => $post->id,
            'title' => $post->title,
            'content' => (string) $post->content,
            'slug' => $post->slug,
            'url' => $post->url,
            'excerpt' => $post->excerpt,
            'featuredImage' => $this->image($post->featuredImage),
            'thumbnail' => $this->image($post->thumbnail),
            'categories' => $this->terms($post->categories),
            'tags' => $this->terms($post->tags),
            'author' => null === $post->author ? null : ['name' => $post->author->name, 'url' => $post->author->url],
            'postClass' => $post->cssClass,
            'linkPages' => (string) $post->linkPages,
            'prev' => $this->link($post->previous),
            'next' => $this->link($post->next),
            'mimeType' => $post->mimeType,
            'isAttachment' => $post->isAttachment(),
            'date' => [
                'display' => $post->dateDisplay,
                'iso' => $post->dateIso,
            ],
        ];
        if ($post->isAttachment()) {
            $data['attachmentUrl'] = (string) $post->attachmentUrl;
        }

        return $data;
    }

    /**
     * @param list<Post> $posts
     *
     * @return list<array<string, mixed>>
     */
    public function buildList(array $posts): array
    {
        return array_map($this->build(...), $posts);
    }

    /** @return array<string, string>|null */
    private function image(?Image $image): ?array
    {
        return null === $image ? null : [
            'url' => $image->url,
            'width' => $image->width,
            'height' => $image->height,
            'alt' => $image->alt,
            'srcset' => $image->srcset,
            'sizes' => $image->sizes,
        ];
    }

    /**
     * @param list<Term> $terms
     *
     * @return list<array<string, string>>
     */
    private function terms(array $terms): array
    {
        return array_map(static fn (Term $t): array => ['name' => $t->name, 'url' => $t->url, 'slug' => $t->slug], $terms);
    }

    /** @return array<string, string>|null */
    private function link(?PostLink $link): ?array
    {
        return null === $link ? null : ['title' => $link->title, 'url' => $link->url];
    }
}
