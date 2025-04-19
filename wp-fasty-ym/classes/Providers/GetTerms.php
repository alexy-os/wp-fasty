<?php

declare(strict_types=1);

namespace WPFasty\Providers;

use WP_Term_Query;
use WP_Term;

class GetTerms
{
    protected array $terms = [];
    protected ?WP_Term_Query $query = null;

    /**
     * Создает экземпляр класса TermCollection из WP_Term_Query
     *
     * @param array $query_args Аргументы для WP_Term_Query
     * @return static
     */
    public static function build(array $query_args): static
    {
        $instance = new static();
        $instance->query = new WP_Term_Query($query_args);

        if (!empty($instance->query->terms)) {
            foreach ($instance->query->terms as $term) {
                $instance->terms[] = $term;
            }
        }

        return $instance;
    }

    /**
     * Создает экземпляр класса TermCollection из массива WP_Term
     *
     * @param array $terms Массив терминов WP_Term
     * @return static
     */
    public static function from_terms(array $terms): static
    {
        $instance = new static();
        foreach ($terms as $term) {
            if ($term instanceof WP_Term) {
                $instance->terms[] = $term;
            }
        }

        return $instance;
    }

    /**
     * Возвращает массив всех терминов
     *
     */
    public function get_terms(): array
    {
        return $this->terms;
    }

    /**
     * Магический метод для динамического доступа к свойствам терминов
     *
     */
    public function __get(string $name): mixed
    {
        $values = [];

        foreach ($this->terms as $term) {
            if (isset($term->$name)) {
                $values[] = $term->$name;
            } else {
                $values[] = null;
            }
        }

        return $values;
    }
}

/*
use WPFasty\PostData;
use WPFasty\TermCollection;

// Внутри цикла WordPress
while (have_posts()) : the_post();
    $postData = PostData::build(get_post());

    // Получение категорий текущего поста
    $categories = get_the_category();
    $categoryCollection = TermCollection::from_terms($categories);

    // Доступ к имени и URL каждой категории
    $category_names = $categoryCollection->name;
    $category_urls = array_map(function($term) {
        return get_category_link($term);
    }, $categoryCollection->get_terms());

    // Пример вывода
    foreach ($category_names as $index => $name) {
        echo '<a href="' . esc_url($category_urls[$index]) . '">' . esc_html($name) . '</a><br>';
    }
endwhile;
*/

/*
// Аргументы для WP_Term_Query
$query_args = [
    'taxonomy' => 'category',
    'hide_empty' => false,
];

$GetTerms = \WPFasty\GetTerms::build($query_args);

// Получение всех терминов
$terms = $GetTerms->get_terms();

// Пример использования
foreach ($terms as $term) {
    echo $term->name; // Доступ к свойству через магический метод __get
}

// Доступ к определенному свойству всех терминов
$term_names = $GetTerms->name;
foreach ($term_names as $name) {
    echo $name;
}
*/

/*
// Аргументы для WP_Term_Query для тегов
$query_args_tags = [
    'taxonomy' => 'post_tag',
    'hide_empty' => false,
];

$tagCollection = \WPFasty\GetTerms::build($query_args_tags);

$tags = $tagCollection->get_terms();
foreach ($tags as $tag) {
    echo $tag->name;
}

// Аргументы для WP_Term_Query для товарных таксономий (например, product_cat)
$query_args_product_cat = [
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
];

$productCatCollection = \WPFasty\GetTerms::build($query_args_product_cat);

$product_cats = $productCatCollection->get_terms();
foreach ($product_cats as $cat) {
    echo $cat->name;
}
*/
