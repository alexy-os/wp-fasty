<?php
/**
 * Creates (or updates) a builder landing page from a JSON spec, publishes its layout and, with "front": true,
 * makes it the static front page (WPFastyBuilder\Seeder::landing).
 *   wp eval-file tools/builder/seed-landing.php tools/builder/landings/press.json
 * Hrefs may use the link tokens of WPFastyBuilder\Links ({home}, {posts}, {page:<slug>}, ...), resolved at render time.
 */

if (!class_exists(WPFastyBuilder\Seeder::class)) {
    WP_CLI::error('Activate the wpfasty-builder plugin first.');
}
$file = $args[0] ?? '';
$spec = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
if (!is_array($spec)) {
    WP_CLI::error("Cannot read $file");
}
$r = WPFastyBuilder\Seeder::landing($spec);
if (is_wp_error($r)) {
    WP_CLI::error($r->get_error_message() . ' ' . wp_json_encode($r->get_error_data()));
}
foreach ($r['issues'] as $i) {
    WP_CLI::warning("{$i['code']} {$i['path']}: {$i['message']}");
}
WP_CLI::success(sprintf('Page %d "%s": layout v%d, %d blocks', $r['page'], get_the_title($r['page']), $r['version'], $r['blocks']));
