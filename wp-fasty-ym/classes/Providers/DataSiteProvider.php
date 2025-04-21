<?php

declare(strict_types=1);

namespace WPFasty\Providers;

class DataSiteProvider implements DataProvider
{
    protected static array $context = [];

    public function __construct()
    {
        //
    }

    public function getContext(array $dataArr = ['site']): array
    {
        if (empty(self::$context)) {
            foreach ($dataArr as $data) {
                switch ($data) {
                    case 'site':
                        self::$context['site'] = self::get_bloginfo();
                        break;
                    case 'theme':
                        self::$context['theme'] = self::get_themeinfo();
                        break;
                    case 'template':
                        self::$context['template'] = self::get_templateinfo();
                        break;
                    default:
                        break;
                }
            }
            self::$context = apply_filters('getdata/context/site', self::$context);
        }

        return self::$context;
    }

    protected static function get_bloginfo(): array
    {
        return [
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => get_bloginfo('url'),
            'siteurl' => site_url(),
            'charset' => get_bloginfo('charset'),
            'language' => get_bloginfo('language'),
            'logourl' => wp_get_attachment_image_src(get_theme_mod('custom_logo'), 'full')[0],
            'textdomain' => self::get_themeinfo()['TextDomain'],
        ];
    }

    protected static function get_themeinfo(): array
    {
        $theme = wp_get_theme();
        return [
            'Name' => $theme->get('Name'),
            'ThemeURI' => $theme->get('ThemeURI'),
            'Description' => $theme->get('Description'),
            'Author' => $theme->get('Author'),
            'AuthorURI' => $theme->get('AuthorURI'),
            'Version' => $theme->get('Version'),
            'Template' => $theme->get('Template'),
            'Status' => $theme->get('Status'),
            'Tags' => $theme->get('Tags'),
            'TextDomain' => $theme->get('TextDomain'),
            'DomainPath' => $theme->get('DomainPath'),
        ];
    }

    protected static function get_templateinfo(): array
    {
        return [
            'template' => get_template(),
            'stylesheet' => get_stylesheet(),
        ];
    }
}

/*
<?php

use WPFasty\DataSiteProvider;

// Пример использования
$data_provider = new DataSiteProvider('your_cache_key');
$context = $data_provider->getContext(['user', 'post', 'posts', 'taxonomies', 'paginated']);

// Пример доступа к данным
echo $context['post']->post_title;
foreach ($context['posts'] as $post) {
    echo $post->post_title;
}
foreach ($context['taxonomies'] as $taxonomy_name => $terms) {
    foreach ($terms as $term) {
        echo $term->name;
    }
}
echo $context['pagination']['current_page'];
*/