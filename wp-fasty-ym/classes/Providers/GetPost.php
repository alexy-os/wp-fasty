<?php

declare(strict_types=1);

namespace WPFasty\Providers;

use WP_Post;

class GetPost
{
    protected ?WP_Post $wp_object = null;

    /**
     * Создает экземпляр класса CustomPost из WP_Post
     *
     * @return static
     */
    public static function build(WP_Post $wp_post): static
    {
        $post = new static();
        $post->wp_object = $wp_post;
        return $post;
    }

    /**
     * Возвращает объект WP_Post
     *
     */
    public function wp_object(): ?WP_Post
    {
        return $this->wp_object;
    }

    public function get_thumbnail()
    {
        $thumb_id = get_post_thumbnail_id();
        $thumb_url = wp_get_attachment_image_src($thumb_id, 'large-size', true);
        return $thumb_url[0];
    }

    /**
     * Получает все данные текущего поста, включая мета-поля
     *
     * @return array Ассоциативный массив со всеми данными поста
     */
    public function get_template_post(): array
    {
        if (!$this->wp_object) {
            return [];
        }

        $post_data = [
            'post' => get_object_vars($this->wp_object),
            'post_meta' => [],
        ];

        // Получение всех мета-полей поста
        $post_meta = get_post_meta($this->wp_object->ID);
        foreach ($post_meta as $key => $values) {
            // Предполагается, что значение мета-поля может быть сериализовано
            $post_data['post_meta'][$key] = maybe_unserialize($values[0]);
        }

        $post_data['post']['thumbnail_url'] = $this->get_thumbnail();

        return $post_data;
    }

    /**
     * Магический метод для динамического доступа к свойствам WP_Post
     *
     */
    public function __get(string $name): mixed
    {
        if (isset($this->wp_object->$name)) {
            return $this->wp_object->$name;
        }

        return null;
    }
}
