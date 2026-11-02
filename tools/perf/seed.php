<?php

/**
 * Seeds realistic archive items for the N+1 guard: featured image, terms, typed field meta and several authors.
 *
 * Usage: wp eval-file tools/perf/seed.php [post_type] [count]
 * post: categories + tags; module types (project, product): their taxonomies + declared fields.
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/image.php';

$wpfastySeedType = $args[0] ?? 'post';
$wpfastySeedCount = (int) ($args[1] ?? 7);

/**
 * Small generated JPEG as attachment (one per item, so thumbnails must be primed per post).
 */
function wpfasty_seed_image(string $name, int $i): int
{
    $file = wp_tempnam($name.'.jpg');
    $img = imagecreatetruecolor(320, 240);
    imagefill($img, 0, 0, imagecolorallocate($img, (40 * $i) % 255, 120, 200));
    imagejpeg($img, $file);
    $id = media_handle_sideload(['name' => $name.'.jpg', 'tmp_name' => $file], 0);
    if (is_wp_error($id)) {
        WP_CLI::error($id->get_error_message());
    }

    return (int) $id;
}

/**
 * @return list<int> term ids
 */
function wpfasty_seed_terms(string $taxonomy, string $prefix): array
{
    $ids = [];
    foreach (['alpha', 'beta', 'gamma'] as $slug) {
        $t = term_exists("$prefix-$slug", $taxonomy) ?: wp_insert_term(ucfirst("$prefix $slug"), $taxonomy, ['slug' => "$prefix-$slug"]);
        if (is_wp_error($t)) {
            WP_CLI::error($t->get_error_message());
        }
        $ids[] = (int) (is_array($t) ? $t['term_id'] : $t);
    }

    return $ids;
}

$wpfastySeedAuthors = [];
foreach (['ann', 'bob', 'cid'] as $login) {
    $u = get_user_by('login', "perf-$login");
    $wpfastySeedAuthors[] = $u ? $u->ID : (int) wp_insert_user([
        'user_login' => "perf-$login",
        'user_pass' => wp_generate_password(),
        'display_name' => ucfirst($login),
        'role' => 'author',
    ]);
}

$wpfastySeedModule = 'post' === $wpfastySeedType
    ? null
    : WPFasty\Core\Application::getInstance()->get('types.registry')->get($wpfastySeedType);
if ('post' !== $wpfastySeedType && null === $wpfastySeedModule) {
    WP_CLI::error("Unknown post type module: $wpfastySeedType (is its theme active?)");
}
$wpfastySeedTax = 'post' === $wpfastySeedType
    ? ['category' => wpfasty_seed_terms('category', 'perf'), 'post_tag' => wpfasty_seed_terms('post_tag', 'perf')]
    : [];
foreach (array_keys($wpfastySeedModule?->taxonomies() ?? []) as $tax) {
    $wpfastySeedTax[$tax] = wpfasty_seed_terms((string) $tax, 'perf');
}

for ($i = 1; $i <= $wpfastySeedCount; ++$i) {
    $id = wp_insert_post([
        'post_type' => $wpfastySeedType,
        'post_status' => 'publish',
        'post_title' => ucfirst($wpfastySeedType)." $i",
        'post_excerpt' => "Excerpt $i",
        'post_content' => "<p>Body of item $i.</p>",
        'post_author' => $wpfastySeedAuthors[$i % 3],
        'post_date' => gmdate('Y-m-d H:i:s', time() - $i * DAY_IN_SECONDS),
    ], true);
    if (is_wp_error($id)) {
        WP_CLI::error($id->get_error_message());
    }
    set_post_thumbnail($id, wpfasty_seed_image("$wpfastySeedType-$i", $i));
    foreach ($wpfastySeedTax as $tax => $terms) {
        wp_set_object_terms($id, [$terms[$i % 3], $terms[($i + 1) % 3]], $tax);
    }
    foreach ($wpfastySeedModule?->fields ?? [] as $name => $field) {
        $value = match ($field->type) {
            'money' => 'sale_price' === $name ? ($i % 2 ? 900 + $i : '') : 1000 + $i * 100,
            'integer' => 5 + $i,
            'url' => "https://example.com/$i",
            default => ucfirst($name)." $i",
        };
        if ('' !== $value) {
            update_post_meta($id, $field->metaKey, $field->toStorage($value));
        }
    }
}
WP_CLI::success(sprintf('%d %s items seeded (thumbnail, %d taxonomies, %d fields)', $wpfastySeedCount, $wpfastySeedType, count($wpfastySeedTax), count($wpfastySeedModule?->fields ?? [])));
