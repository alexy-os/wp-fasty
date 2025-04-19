<?php

declare(strict_types=1);

namespace WPFasty\Providers;

class DataProviderManager implements DataProvider
{
    protected static array $context = [];
    protected static array $data_providers = [];

    public function __construct(array $data_providers)
    {
        self::$data_providers = $data_providers;
    }

    public function getContext(array $dataArr = ['posts']): array
    {
        if (empty(self::$context)) {
            foreach (self::$data_providers as $provider) {
                $provider_context = $provider->getContext($dataArr);
                self::$context = array_merge(self::$context, $provider_context);
            }

            self::$context = apply_filters('getdata/context', self::$context);
        }

        return self::$context;
    }
}

/*
<?php

use WPFasty\DataProviderManager;
use WPFasty\GetDataQuery;
use WPFasty\GetDataSite;
use WPFasty\QueryConfigurator;

$query_args = [
    'post_type' => 'post',
    'posts_per_page' => 10,
    'paged' => get_query_var('paged', 1),
];

$configurator = new QueryConfigurator($query_args);
$data_provider_query = new GetDataQuery('your_cache_key_query', $configurator);
$data_provider_site = new GetDataSite('your_cache_key_site');

$data_providers = [$data_provider_query, $data_provider_site];
$data_provider_manager = new DataProviderManager('your_cache_key', $data_providers);

$context = $data_provider_manager->getContext(['posts', 'site', 'theme', 'template']);

// Пример доступа к данным в шаблоне
echo $context['post']->post_title;
echo $context['site']['name'];
echo $context['theme']['name'];
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