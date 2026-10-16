<?php
/**
 * Creates (or updates) a builder landing page from a JSON spec and publishes its layout.
 *   wp eval-file tools/builder/seed-landing.php tools/builder/landings/press.json
 * Hrefs may use the link tokens of WPFastyBuilder\Links ({home}, {posts}, {page:<slug>}, ...), resolved at render time.
 * With "front": true the page becomes the static front page.
 */

if (!class_exists(WPFastyBuilder\Store::class)) {
    WP_CLI::error('Activate the wpfasty-builder plugin first.');
}
$file = $args[0] ?? '';
$spec = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
if (!is_array($spec)) {
    WP_CLI::error("Cannot read $file");
}

$page = $spec['page'] ?? [];
$existing = get_page_by_path((string) ($page['slug'] ?? 'home'));
$id = $existing ? $existing->ID : wp_insert_post([
    'post_type' => 'page',
    'post_status' => 'publish',
    'post_title' => (string) ($page['title'] ?? 'Home'),
    'post_name' => (string) ($page['slug'] ?? 'home'),
    'post_content' => '',
], true);
if (is_wp_error($id)) {
    WP_CLI::error($id->get_error_message());
}

if (!empty($spec['front'])) {
    if ((int) get_option('page_for_posts') === 0 && get_option('show_on_front') === 'posts') {
        $blog = get_page_by_path('blog') ?: null;
        $blogId = $blog ? $blog->ID : wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Blog', 'post_name' => 'blog']);
        update_option('page_for_posts', (int) $blogId);
    }
    update_option('show_on_front', 'page');
    update_option('page_on_front', (int) $id);
}
$layout = $spec['layout'];
$result = WPFastyBuilder\Store::save((int) $id, $layout, true);
if (is_wp_error($result)) {
    WP_CLI::error($result->get_error_message() . ' ' . wp_json_encode($result->get_error_data()));
}
[$entry, $issues] = $result;
foreach ($issues as $i) {
    WP_CLI::warning("{$i['code']} {$i['path']}: {$i['message']}");
}
WP_CLI::success(sprintf('Page %d "%s": layout v%d, %d blocks', $id, get_the_title((int) $id), $entry['version'], count($entry['metadata']['blocks']['value'])));
