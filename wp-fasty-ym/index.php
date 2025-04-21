<?php
/**
 * Main template file
 *
 * @package WPFasty
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Проверяем, активирован ли Go Frontend и хотим ли мы его использовать
if (class_exists('WPFasty_Go_Frontend') && has_action('wpfasty_go_frontend')) {
    // Выводим Go Frontend
    get_header();

    // Отображаем Go Frontend
    do_action('wpfasty_go_frontend', [
        'cache' => true
    ]);

    get_footer();
} else {
    // Если Go Frontend не доступен, используем стандартный шаблон WordPress
    get_header(); ?>

    <main id="primary" class="site-main">
        <?php if (have_posts()) : ?>
            <?php while (have_posts()) :
                the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </header>

                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                </article>
            <?php endwhile; ?>
            
            <?php the_posts_navigation(); ?>
        <?php else : ?>
            <p><?php esc_html_e('Nothing found', 'wp-fasty-ym'); ?></p>
        <?php endif; ?>
    </main>

    <?php get_footer();
}
