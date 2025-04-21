<?php

declare(strict_types=1);

namespace WPFasty\Providers;

use WPFasty\Core\GetPost;
use WPFasty\Core\GetPosts;
use WPFasty\Core\GetTerms;
use WPFasty\Core\Pagination;

use WP_Query;
use WP_User;

class DataQueryProvider implements DataProvider
{
    protected static array $context = [];

    public function __construct(QueryConfigurator $configurator)
    {
        $this->configurator = $configurator;
    }

    public function getContext(array $dataArr = ['posts']): array
    {
        if (empty(self::$context)) {
            foreach ($dataArr as $data) {
                switch ($data) {
                    case 'user':
                        self::$context['user'] = $this->getUserData();
                        break;
                    case 'post':
                        self::$context = $this->getPostData();
                        break;
                    case 'posts':
                        self::$context['posts'] = $this->getPostsData();
                        break;
                    case 'taxonomies':
                        self::$context['taxonomies'] = $this->getTaxonomiesData();
                        break;
                    case 'paginated':
                        self::$context['pagination'] = $this->getPaginationData();
                        break;
                    default:
                        break;
                }
            }
            self::$context = apply_filters('getdata/context', self::$context);
        }

        return self::$context;
    }

    protected function getUserData(): array
    {
        $current_user = is_user_logged_in() ? wp_get_current_user() : null;
        return $current_user ? (array) $current_user->data : [];
    }

    protected function getPostData(): array
    {
        global $post;
        $post_data = GetPost::build($post);
        return $post ? (array) $post_data->get_template_post() : [];
    }

    protected function getPostsData(): array
    {
        $query_args = $this->configurator->getQueryArgs();
        $GetPosts = GetPosts::build($query_args);
        $posts = $GetPosts->get_posts();

        return $posts;
    }

    protected function getTaxonomiesData(): array
    {
        global $post;
        if (!$post) {
            return [];
        }

        $terms = [];
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        foreach ($taxonomies as $taxonomy) {
            $terms[$taxonomy->name] = GetTerms::build(get_the_terms($post, $taxonomy->name));
        }

        return $terms;
    }

    protected function getPaginationData(): array
    {
        $query_args = $this->configurator->getQueryArgs();
        $query = new WP_Query($query_args);
        return (array) Pagination::build($query);
    }
}

/*
<?php

use WPFasty\GetDataQuery;
use WPFasty\QueryConfigurator;

$query_args = [
    'post_type' => 'post',
    'posts_per_page' => 10,
    'paged' => get_query_var('paged', 1),
];

$configurator = new QueryConfigurator($query_args);
$data_provider = new GetDataQuery('your_cache_key', $configurator);
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