<?php
/* Template Name: Full Page Template */

get_header();

// Получаем относительный путь к статическому файлу
$static_file_path = get_post_meta(get_the_ID(), '_wp_fasty_static_file', true);
if ($static_file_path && file_exists(get_template_directory() . $static_file_path)) {
    // Просто включаем отрендеренный файл
    include(get_template_directory() . $static_file_path);
} else {
    // Если файл не найден, выводим стандартный контент
    the_content();
}

get_footer();
