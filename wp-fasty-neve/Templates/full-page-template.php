<?php
/* Template Name: Full Page Template */

// Отключаем фильтры форматирования перед выводом контента
remove_filter('the_content', 'wpautop');
remove_filter('the_content', 'wptexturize');
remove_filter('the_content', 'convert_chars');

// Отключаем теги shortcode_unautop
remove_filter('the_content', 'shortcode_unautop');

// Выводим необработанный контент
echo get_post_field('post_content', get_the_ID(), 'raw');