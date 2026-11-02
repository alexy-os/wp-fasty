<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Model\Logo;
use WPFasty\Core\Model\SiteInfo;
use WPFasty\Core\Port\SiteInfoProviderInterface;

final class WordPressSiteInfoProvider implements SiteInfoProviderInterface
{
    public function site(): SiteInfo
    {
        $postsPageId = (int) get_option('page_for_posts');

        return new SiteInfo(
            get_bloginfo('name'),
            home_url('/'),
            get_template_directory_uri(),
            get_stylesheet_directory_uri(),
            get_bloginfo('language'),
            get_bloginfo('description'),
            get_bloginfo('charset'),
            $this->logo(),
            $postsPageId > 0 ? (string) get_permalink($postsPageId) : home_url('/'),
        );
    }

    private function logo(): ?Logo
    {
        if (!function_exists('has_custom_logo') || !has_custom_logo()) {
            return null;
        }
        $id = (int) get_theme_mod('custom_logo');
        if ($id <= 0) {
            return null;
        }
        $src = wp_get_attachment_image_src($id, 'full');
        if (!$src) {
            return null;
        }

        return new Logo(
            $src[0],
            $src[1],
            $src[2],
            (string) get_post_meta($id, '_wp_attachment_image_alt', true) ?: get_bloginfo('name'),
        );
    }
}
