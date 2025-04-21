<?php

declare(strict_types=1);

namespace WPFasty\Providers;

use WP_Query;

class Pagination
{
    protected array $pagination_data = [];
    protected ?WP_Query $query = null;

    /**
     * Создает экземпляр класса Pagination из WP_Query
     *
     * @param WP_Query $query Экземпляр WP_Query
     * @return static
     */
    public static function build(WP_Query $query): static
    {
        $instance = new static();
        $instance->query = $query;

        $instance->pagination_data = [
            'current_page' => max(1, get_query_var('paged')),
            'total_pages' => $query->max_num_pages,
            'total_items' => $query->found_posts,
            'posts_per_page' => $query->query_vars['posts_per_page'],
            'has_previous' => $query->query_vars['paged'] > 1,
            'has_next' => $query->query_vars['paged'] < $query->max_num_pages,
            'previous_page' => max(1, $query->query_vars['paged'] - 1),
            'next_page' => min($query->max_num_pages, $query->query_vars['paged'] + 1),
        ];

        return $instance;
    }

    /**
     * Магический метод для динамического доступа к свойствам пагинации
     *
     */
    public function __get(string $name): mixed
    {
        return $this->pagination_data[$name] ?? null;
    }

    /**
     * Получает URL для страницы
     *
     */
    public function get_page_url(int $page): string
    {
        return get_pagenum_link($page);
    }
}

/*
use WPFasty\GetPost;
use WPFasty\Pagination;

// Параметры WP_Query
$query_args = [
    'post_type' => 'post',
    'posts_per_page' => 10,
    'paged' => get_query_var('paged', 1),
];

$query = new WP_Query($query_args);

// Получение данных постов
$posts = [];
if ($query->have_posts()) {
    while ($query->have_posts()) {
        $query->the_post();
        $posts[] = GetPost::build(get_post());
    }
}

// Получение данных пагинации
$pagination = Pagination::build($query);

// Пример вывода постов и пагинации в шаблоне
foreach ($posts as $post) {
    echo '<h2>' . $post->post_title . '</h2>';
}

echo '<div class="pagination">';
if ($pagination->has_previous) {
    echo '<a href="' . $pagination->get_page_url($pagination->previous_page) . '">Previous</a>';
}
for ($i = 1; $i <= $pagination->total_pages; $i++) {
    echo '<a href="' . $pagination->get_page_url($i) . '">' . $i . '</a>';
}
if ($pagination->has_next) {
    echo '<a href="' . $pagination->get_page_url($pagination->next_page) . '">Next</a>';
}
echo '</div>';

// Восстановление глобального поста
wp_reset_postdata();
*/
