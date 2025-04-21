<?php
/**
 * Template Name: Go Frontend
 *
 * Шаблон страницы, использующий Go-фронтенд для рендеринга
 *
 * @package WPFasty
 */

if (!defined('ABSPATH')) {
    exit;
}

// Подключаем компонент Go Frontend, если он еще не подключен
$go_frontend_component = get_template_directory() . '/components/go-frontend.php';
if (file_exists($go_frontend_component)) {
    include_once($go_frontend_component);
}

get_header();

// Проверяем, существует ли действие wpfasty_go_frontend
if (has_action('wpfasty_go_frontend')) {
    /**
     * Запускаем хук, который вызовет рендер Go-фронтенда
     */
    do_action('wpfasty_go_frontend', [
        'cache' => !WP_DEBUG, // Отключаем кэширование в режиме отладки
        'cache_time' => 1800 // Полчаса кэширование
    ]);
} else {
    // Если компонент недоступен, показываем стандартный контент страницы
    echo '<div class="container mx-auto px-4 py-8">';

    if (have_posts()) {
        while (have_posts()) {
            the_post();

            echo '<h1 class="text-3xl font-bold mb-6">' . esc_html(get_the_title()) . '</h1>';
            echo '<div class="content">' . wp_kses_post(get_the_content()) . '</div>';
        }
    } else {
        echo '<p>Контент не найден.</p>';
    }

    echo '<div class="alert alert-warning mt-6 p-4 bg-yellow-100 text-yellow-800 rounded">
            <p><strong>Внимание:</strong> Компонент Go Frontend не найден или не активирован.</p>
            <p>Для активации этого шаблона убедитесь, что файл компонента существует:</p>
            <code>' . esc_html($go_frontend_component) . '</code>
            <p>И что Go бинарный файл скомпилирован и доступен.</p>
          </div>';

    echo '</div>';
}

get_footer();
