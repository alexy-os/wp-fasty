<?php

require __DIR__ . '/common.php';

wpfasty_demo_cleanup_defaults();
// The active shop child (latte-shop or a generated twin such as latte-shop-twig): its name and its Front class.
$theme = wp_get_theme();
$front = str_replace(' ', '', (string) $theme->get('Name')) . '\\Front';
if (!class_exists($front)) {
    $front = 'LatteShop\\Front';
}
update_option('blogname', (string) $theme->get('Name'));
update_option('blogdescription', 'Everyday goods, simply made');

$cats = [];
foreach (['Kitchen', 'Home', 'Accessories'] as $name) {
    $t = term_exists($name, 'product_cat') ?: wp_insert_term($name, 'product_cat');
    $cats[$name] = (int) (is_array($t) ? $t['term_id'] : $t);
}

update_option('wpfasty_currency', 'USD');

// price / sale in integer minor units (cents); null = not set; stock null = unlimited;
// rating 0-5, reviews count, badge (null = "sale" when discounted, set by the builder's purchasable projection).
$products = [
    ['ceramic-mug', 'Ceramic mug', 'Kitchen', 1800, null, 'MUG-01', null, 'ceramic-mug', 4.8, 126, null],
    ['pour-over-set', 'Pour-over coffee set', 'Kitchen', 5400, 4500, 'POUR-02', 12, 'pour-over', 4.6, 58, null],
    ['linen-apron', 'Linen apron', 'Kitchen', 3600, null, 'APR-03', null, 'linen-apron', 4.4, 31, null],
    ['wool-throw', 'Wool throw blanket', 'Home', 12000, 9600, 'THR-04', 5, 'wool-throw', 4.9, 212, null],
    ['stoneware-vase', 'Stoneware vase', 'Home', 4200, null, 'VAS-05', null, 'stoneware-vase', 4.5, 17, null],
    ['oak-tray', 'Oak serving tray', 'Home', 6400, null, 'TRY-06', 0, 'oak-tray', 4.2, 9, null],
    ['canvas-tote', 'Canvas tote bag', 'Accessories', 2800, null, 'TOT-07', null, 'canvas-tote', 4.3, 44, 'new'],
    ['leather-wallet', 'Leather card wallet', 'Accessories', 4800, 3900, 'WAL-08', 20, 'leather-wallet', 4.7, 88, null],
];
$order = 0;
foreach ($products as [$slug, $title, $cat, $price, $sale, $sku, $stock, $img, $rating, $reviews, $badge]) {
    $id = wpfasty_demo_post($slug, [
        'post_type' => 'product',
        'post_title' => $title,
        'post_excerpt' => "A {$cat}-ready essential, made to last and designed to be used every day.",
        'post_content' => wpfasty_demo_lorem(3, false),
        'menu_order' => $order++,
    ]);
    wp_set_object_terms($id, [$cats[$cat]], 'product_cat');
    $meta = [
        '_shop_price' => $price, '_shop_sale_price' => $sale, '_shop_sku' => $sku, '_shop_stock' => $stock,
        '_shop_rating' => $rating, '_shop_reviews' => $reviews, '_shop_badge' => $badge,
    ];
    foreach ($meta as $key => $value) {
        if ($value === null) {
            delete_post_meta($id, $key);
        } else {
            update_post_meta($id, $key, $value);
        }
    }
    $att = wpfasty_demo_image('shop-' . $img, 1000, 1000);
    if ($att) {
        set_post_thumbnail($id, $att);
    }
}

$front::ensurePages();
$home = wpfasty_demo_post('home', [
    'post_type' => 'page',
    'post_title' => 'Everyday goods, simply made.',
    'post_excerpt' => 'Small-batch kitchen and home essentials. A WooCommerce-free demo store built on ' . ucfirst(get_template()) . ' + UI8Kit.',
    'post_content' => '',
]);
$hero = wpfasty_demo_image('shop-hero-table', 1200, 900);
if ($hero) {
    set_post_thumbnail($home, $hero);
}
update_option('show_on_front', 'page');
update_option('page_on_front', $home);
$about = wpfasty_demo_post('about', ['post_type' => 'page', 'post_title' => 'About', 'post_content' => wpfasty_demo_lorem(2, false)]);

wpfasty_demo_menu('Primary', 'primary', [['Shop', get_post_type_archive_link('product')], ['Kitchen', get_term_link($cats['Kitchen'])], ['Home', get_term_link($cats['Home'])], ['About', get_permalink($about)]]);
wpfasty_demo_menu('Footer', 'footer', [['Shop', get_post_type_archive_link('product')], ['Cart', $front::pageUrl('cart')], ['About', get_permalink($about)]]);
wpfasty_demo_contact([
    'contact_phone' => '+12025550143',
    'contact_email' => 'hello@example.com',
    'contact_address' => '742 Evergreen Terrace, Springfield, IL 62704, USA',
    'contact_links' => [
        ['network' => 'whatsapp', 'handle' => '+12025550143'],
        ['network' => 'telegram', 'handle' => 'latte_shop_demo'],
        ['network' => 'instagram', 'handle' => 'latte.shop.demo'],
    ],
    'hours' => [
        ['days' => ['mon', 'tue', 'wed', 'thu', 'fri'], 'open' => '09:00', 'close' => '18:00'],
        ['days' => ['sat'], 'open' => '10:00', 'close' => '16:00'],
    ],
    'hours_exceptions' => [
        ['date' => '2026-12-25', 'closed' => true],
        ['date' => '2026-12-31', 'open' => '10:00', 'close' => '14:00'],
    ],
    'map' => ['provider' => 'osm', 'lat' => 39.7817, 'lng' => -89.6501],
], 'America/Chicago');
flush_rewrite_rules();

wpfasty_demo_log($theme->get('Name') . ' demo content ready.');
