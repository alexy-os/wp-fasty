<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Data\Seo\MetaDescriptionBuilder;

/**
 * `<meta name="description">` of the current request: the excerpt of a single entry, the description of a term or
 * author archive, else the site tagline (else the site title). Filter `wpfasty_meta_description` (string, '' = no tag). Skipped when an SEO
 * plugin that prints its own description is active (Yoast, Rank Math, AIOSEO, SEOPress, The SEO Framework).
 */
final class WordPressMetaDescription
{
    public function __construct(private readonly MetaDescriptionBuilder $builder)
    {
    }

    public static function seoPluginActive(): bool
    {
        return defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION')
            || defined('SEOPRESS_VERSION') || defined('THE_SEO_FRAMEWORK_VERSION');
    }

    public function current(): string
    {
        $text = '';
        $post = is_singular() ? get_queried_object() : null;
        if ($post instanceof \WP_Post && !post_password_required($post)) {
            $text = has_excerpt($post)
                ? (string) $post->post_excerpt
                : wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 40);
        } elseif (is_category() || is_tag() || is_tax() || is_author()) {
            $text = (string) get_the_archive_description();
        }
        $text = PlainText::from($text);
        if ('' === trim($text)) {
            $text = PlainText::from((string) get_bloginfo('description'));
        }
        if ('' === trim($text)) {
            $text = PlainText::from((string) get_bloginfo('name')); // no tagline set: the site title
        }

        return (string) apply_filters('wpfasty_meta_description', $this->builder->build($text));
    }

    public function tag(): string
    {
        if (self::seoPluginActive()) {
            return '';
        }
        $description = $this->current();

        return '' === $description ? '' : '<meta name="description" content="'.esc_attr($description).'" />'."\n";
    }
}
