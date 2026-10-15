<?php


require __DIR__ . '/common.php';

wpfasty_demo_cleanup_defaults();
update_option('blogname', 'Latte Press');
update_option('blogdescription', 'Notes on calm, fast WordPress design');
update_option('show_on_front', 'posts');
update_option('posts_per_page', 6);
update_option('thread_comments', 1);
update_option('comment_moderation', 1);

$cats = ['Design' => 0, 'Engineering' => 0, 'Notes' => 0];
foreach (array_keys($cats) as $name) {
    $t = term_exists($name, 'category') ?: wp_insert_term($name, 'category');
    $cats[$name] = (int) (is_array($t) ? $t['term_id'] : $t);
}

$posts = [
    ['the-8px-grid', 'Why we design on an 8px grid', 'Design', 'grid-paper'],
    ['tokens-not-colors', 'Tokens, not colors', 'Design', 'paint-swatches'],
    ['latte-templates', 'Latte templates for WordPress', 'Engineering', 'coffee-latte'],
    ['ui8kit-primitives', 'Building with UI8Kit primitives', 'Engineering', 'building-blocks'],
    ['lucide-icons', 'Icons that inherit their color', 'Design', 'lines-icons'],
    ['fast-by-default', 'Fast by default', 'Engineering', 'speed-road'],
    ['on-writing', 'On writing short posts', 'Notes', 'notebook-pen'],
    ['quiet-interfaces', 'Quiet interfaces', 'Notes', 'quiet-lake'],
];
$i = 0;
foreach ($posts as [$slug, $title, $cat, $img]) {
    $id = wpfasty_demo_post($slug, [
        'post_title' => $title,
        'post_content' => wpfasty_demo_lorem(5),
        'post_excerpt' => '',
        'post_category' => [$cats[$cat]],
        'post_date' => gmdate('Y-m-d H:i:s', strtotime('-' . ($i * 3 + 1) . ' days')),
        'tags_input' => ['wordpress', strtolower($cat)],
        'comment_status' => 'open',
    ]);
    $att = wpfasty_demo_image($img);
    if ($att) {
        set_post_thumbnail($id, $att);
    }
    $i++;
}

$first = get_page_by_path('the-8px-grid', OBJECT, 'post');
if ($first && (int) get_comments_number($first->ID) === 0) {
    $c = wp_insert_comment(['comment_post_ID' => $first->ID, 'comment_author' => 'Maria', 'comment_author_email' => 'maria@example.com', 'comment_content' => 'This finally explains why my layouts felt uneven. Thanks!', 'comment_approved' => 1]);
    wp_insert_comment(['comment_post_ID' => $first->ID, 'comment_author' => 'Latte Press', 'comment_author_email' => 'admin@example.com', 'comment_content' => 'Glad it helped — eight is a forgiving number.', 'comment_parent' => $c, 'comment_approved' => 1, 'user_id' => 1]);
}

$about = wpfasty_demo_post('about', ['post_type' => 'page', 'post_title' => 'About', 'post_content' => wpfasty_demo_lorem(3, false)]);
wpfasty_demo_menu('Primary', 'primary', [['Home', home_url('/')], ['Design', get_category_link($cats['Design'])], ['Engineering', get_category_link($cats['Engineering'])], ['About', get_permalink($about)]]);
wpfasty_demo_menu('Footer', 'footer', [['About', get_permalink($about)], ['RSS', get_feed_link()]]);

// Sidebar widgets: search, recent posts, categories, archives.
update_option('widget_search', [2 => ['title' => ''], '_multiwidget' => 1]);
update_option('widget_recent-posts', [2 => ['title' => 'Recent posts', 'number' => 5], '_multiwidget' => 1]);
update_option('widget_categories', [2 => ['title' => 'Categories', 'count' => 1], '_multiwidget' => 1]);
update_option('widget_archives', [2 => ['title' => 'Archives'], '_multiwidget' => 1]);
update_option('sidebars_widgets', ['wp_inactive_widgets' => [], 'sidebar-1' => ['search-2', 'recent-posts-2', 'categories-2', 'archives-2'], 'footer-1' => [], 'array_version' => 3]);

WP_CLI::success('Latte Press demo content ready.');
