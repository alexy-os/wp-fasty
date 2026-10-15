<?php

/**
 * Shared demo-content helpers. Run via: wp eval-file tools/demo/<theme>.php
 * Idempotent-ish: content is keyed by slug; images by source URL.
 */


if (!defined('ABSPATH')) {
    exit;
}

require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function wpfasty_demo_image(string $seed, int $w = 1200, int $h = 800): int
{
    $url = "https://picsum.photos/seed/{$seed}/{$w}/{$h}.jpg";
    $existing = get_posts(['post_type' => 'attachment', 'meta_key' => '_wpfasty_demo_src', 'meta_value' => $url, 'fields' => 'ids', 'numberposts' => 1]);
    if ($existing) {
        return (int) $existing[0];
    }
    // Plain HTTP fetch (CLI-only demo tool; wp_safe_remote_get rejects some sandbox resolvers).
    $tmp = wp_tempnam($seed . '.jpg');
    $res = wp_remote_get($url, ['timeout' => 30, 'stream' => true, 'filename' => $tmp, 'redirection' => 5]);
    if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
        WP_CLI::warning("image {$seed}: " . (is_wp_error($res) ? $res->get_error_message() : wp_remote_retrieve_response_code($res)));
        @unlink($tmp);
        return 0;
    }
    $id = media_handle_sideload(['name' => "{$seed}.jpg", 'tmp_name' => $tmp], 0);
    if (is_wp_error($id)) {
        @unlink($tmp);
        return 0;
    }
    update_post_meta($id, '_wpfasty_demo_src', $url);
    update_post_meta($id, '_wp_attachment_image_alt', ucwords(str_replace('-', ' ', $seed)));
    return (int) $id;
}

/** @param array<string, mixed> $args */
function wpfasty_demo_post(string $slug, array $args): int
{
    $type = $args['post_type'] ?? 'post';
    $found = get_page_by_path($slug, OBJECT, $type);
    $data = array_merge(['post_name' => $slug, 'post_status' => 'publish', 'post_type' => $type], $args);
    if ($found) {
        $data['ID'] = $found->ID;
        return (int) wp_update_post($data);
    }
    return (int) wp_insert_post($data);
}

function wpfasty_demo_lorem(int $paras = 4, bool $rich = true): string
{
    $p = [
        'Good design is as little design as possible. We strip away everything that does not serve the reader, and what remains is calm, legible and quick to load.',
        'Every layout here sits on an 8px grid. Spacing inside controls may use 4px steps; everything else moves in multiples of eight, which keeps rhythm consistent across pages.',
        'Colors are never hard-coded. Each theme ships a small set of CSS variable tokens — background, foreground, primary, muted, border — and every component reads from them.',
        'Templates are written in Latte and composed from UI8Kit primitives: Box, Stack, Card, Button, Badge. The same contract renders identically in PHP, React or Go.',
        'Icons come from Lucide and are inlined as SVG with proper aria attributes, so they inherit the current text color and scale with the type.',
        'Content is prepared in PHP as plain arrays, so templates never call WordPress directly. That separation makes views easy to test and easy to reuse across themes.',
    ];
    $out = '';
    for ($i = 0; $i < $paras; $i++) {
        $out .= "<!-- wp:paragraph --><p>{$p[$i % count($p)]}</p><!-- /wp:paragraph -->\n";
        if ($rich && $i === 1) {
            $out .= "<!-- wp:heading --><h2>Why it matters</h2><!-- /wp:heading -->\n";
            $out .= "<!-- wp:quote --><blockquote class=\"wp-block-quote\"><p>Simplicity is the ultimate sophistication.</p></blockquote><!-- /wp:quote -->\n";
        }
        if ($rich && $i === 2) {
            $out .= "<!-- wp:list --><ul><li>8px layout grid</li><li>Token-only colors</li><li>Lucide icons</li></ul><!-- /wp:list -->\n";
        }
    }
    return $out;
}

/** @param list<array{0:string,1:string}> $items [title, url] */
function wpfasty_demo_menu(string $name, string $location, array $items): void
{
    $menu = wp_get_nav_menu_object($name);
    $id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);
    foreach (wp_get_nav_menu_items($id) ?: [] as $old) {
        wp_delete_post($old->ID, true);
    }
    foreach ($items as [$title, $url]) {
        wp_update_nav_menu_item($id, 0, ['menu-item-title' => $title, 'menu-item-url' => $url, 'menu-item-status' => 'publish', 'menu-item-type' => 'custom']);
    }
    $locations = get_theme_mod('nav_menu_locations', []);
    $locations[$location] = $id;
    set_theme_mod('nav_menu_locations', $locations);
}

function wpfasty_demo_cleanup_defaults(): void
{
    foreach (['hello-world' => 'post', 'sample-page' => 'page'] as $slug => $type) {
        $p = get_page_by_path($slug, OBJECT, $type);
        if ($p) {
            wp_delete_post($p->ID, true);
        }
    }
}
