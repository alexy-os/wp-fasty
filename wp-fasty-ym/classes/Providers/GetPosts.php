<?php

declare(strict_types=1);

namespace WPFasty\Providers;

use WP_Query;
use WP_Post;

class GetPosts
{
    protected array $posts = [];
    protected ?WP_Query $query = null;

    /**
     * Создает экземпляр класса GetPosts из WP_Query
     *
     * @param array $query_args Аргументы для WP_Query
     * @return static
     */
    public static function build(array $query_args): static
    {
        $instance = new static();
        $instance->query = new WP_Query($query_args['query_args']);

        if ($instance->query->have_posts()) {
            while ($instance->query->have_posts()) {
                $instance->query->the_post();
                $post_data = GetPost::build($instance->query->post);
                $instance->posts[] = $post_data->get_template_post();
            }
            wp_reset_postdata();
        }

        return $instance;
    }

    /**
     * Возвращает массив всех постов
     *
     */
    public function get_posts(): array
    {
        return $this->posts;
    }

    /**
     * Магический метод для динамического доступа к свойствам постов
     *
     */
    public function __get(string $name): mixed
    {
        $values = [];

        foreach ($this->posts as $post) {
            if (isset($post->$name)) {
                $values[] = $post->$name;
            } else {
                $values[] = null;
            }
        }

        return $values;
    }
}

/*
// Аргументы для WP_Query
$query_args = [
    'post_type' => 'post',
    'posts_per_page' => 10,
];

$GetPosts = \WPFasty\GetPosts::build($query_args);

// Получение всех постов
$posts = $GetPosts->get_posts();

// Пример использования
foreach ($posts as $post) {
    echo $post->post_title; // Доступ к свойству через магический метод __get
}

// Доступ к определенному свойству всех постов
$post_titles = $GetPosts->post_title;
foreach ($post_titles as $title) {
    echo $title;
}
*/
