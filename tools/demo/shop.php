<?php

require __DIR__ . '/common.php';

wpfasty_demo_cleanup_defaults();
update_option('blogname', 'Latte Shop');
update_option('blogdescription', 'Everyday goods, simply made');

$cats = [];
foreach (['Kitchen', 'Home', 'Accessories'] as $name) {
    $t = term_exists($name, 'product_cat') ?: wp_insert_term($name, 'product_cat');
    $cats[$name] = (int) (is_array($t) ? $t['term_id'] : $t);
}

update_option('wpfasty_currency', 'USD');

// price / sale in integer minor units (cents); null = not set; stock null = unlimited.
$products = [
    ['ceramic-mug', 'Ceramic mug', 'Kitchen', 1800, null, 'MUG-01', null, 'ceramic-mug'],
    ['pour-over-set', 'Pour-over coffee set', 'Kitchen', 5400, 4500, 'POUR-02', 12, 'pour-over'],
    ['linen-apron', 'Linen apron', 'Kitchen', 3600, null, 'APR-03', null, 'linen-apron'],
    ['wool-throw', 'Wool throw blanket', 'Home', 12000, 9600, 'THR-04', 5, 'wool-throw'],
    ['stoneware-vase', 'Stoneware vase', 'Home', 4200, null, 'VAS-05', null, 'stoneware-vase'],
    ['oak-tray', 'Oak serving tray', 'Home', 6400, null, 'TRY-06', 0, 'oak-tray'],
    ['canvas-tote', 'Canvas tote bag', 'Accessories', 2800, null, 'TOT-07', null, 'canvas-tote'],
    ['leather-wallet', 'Leather card wallet', 'Accessories', 4800, 3900, 'WAL-08', 20, 'leather-wallet'],
];
$order = 0;
foreach ($products as [$slug, $title, $cat, $price, $sale, $sku, $stock, $img]) {
    $id = wpfasty_demo_post($slug, [
        'post_type' => 'product',
        'post_title' => $title,
        'post_excerpt' => "A {$cat}-ready essential, made to last and designed to be used every day.",
        'post_content' => wpfasty_demo_lorem(3, false),
        'menu_order' => $order++,
    ]);
    wp_set_object_terms($id, [$cats[$cat]], 'product_cat');
    foreach (['_shop_price' => $price, '_shop_sale_price' => $sale, '_shop_sku' => $sku, '_shop_stock' => $stock] as $key => $value) {
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

LatteShop\Front::ensurePages();
$home = wpfasty_demo_post('home', [
    'post_type' => 'page',
    'post_title' => 'Everyday goods, simply made.',
    'post_excerpt' => 'Small-batch kitchen and home essentials. A WooCommerce-free demo store built on Latte + UI8Kit.',
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
wpfasty_demo_menu('Footer', 'footer', [['Shop', get_post_type_archive_link('product')], ['Cart', LatteShop\Front::pageUrl('cart')], ['About', get_permalink($about)]]);
flush_rewrite_rules();

wpfasty_demo_log('Latte Shop demo content ready.');
