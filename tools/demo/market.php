<?php

/**
 * WP FastY marketplace demo: 13 items (sites / themes / plugins), legal pages, menus.
 * Run: wp eval-file tools/demo/market.php  (images from tools/demo/images/market-*.jpg; legal from $WPFASTY_LEGAL_JSON).
 */

require __DIR__ . '/common.php';

wpfasty_demo_cleanup_defaults();
$front = 'WpfastyMarket\\Front';
update_option('blogname', 'WP FastY');
update_option('blogdescription', 'Маркетплейс готовых сайтов, тем и плагинов WordPress');
update_option('wpfasty_currency', 'RUB');

$cats = [];
foreach (['sites' => 'Готовые сайты', 'themes' => 'Темы', 'plugins' => 'Плагины'] as $slug => $name) {
    $t = term_exists($slug, 'product_cat') ?: wp_insert_term($name, 'product_cat', ['slug' => $slug]);
    $cats[$slug] = (int) (is_array($t) ? $t['term_id'] : $t);
}

// [slug, title, cat, price ₽, sale ₽|null, rating, reviews, badge, excerpt]
$items = [
    ['fasty-agency', 'FastY Agency — сайт digital-агентства', 'sites', 14900, 11900, 4.9, 312, 'Хит', 'Готовый сайт агентства: кейсы, услуги, команда, блог и заявки. Импорт за 10 минут.'],
    ['fasty-store', 'FastY Store — интернет-магазин под ключ', 'sites', 19900, null, 4.8, 204, null, 'Магазин без WooCommerce: каталог, корзина, оформление заказа и личный кабинет.'],
    ['clinic-pro', 'Clinic Pro — сайт медицинского центра', 'sites', 12900, null, 4.7, 88, null, 'Врачи, услуги, прайс, онлайн-запись и отзывы пациентов.'],
    ['edu-campus', 'Edu Campus — онлайн-школа', 'sites', 16900, null, 4.6, 131, null, 'Курсы, преподаватели, расписание и лендинги для набора.'],
    ['restoran', 'Restoran — сайт ресторана', 'sites', 9900, null, 4.6, 21, 'Новинка', 'Меню, бронирование столов, банкеты и доставка.'],
    ['latte-magazine', 'Latte Magazine — тема для медиа', 'themes', 5900, 4900, 4.9, 540, 'Хит', 'Быстрая журнальная тема: рубрики, авторы, подборки и рекламные места.'],
    ['folio-noir', 'Folio Noir — тема портфолио', 'themes', 4900, null, 4.7, 176, null, 'Тёмное портфолио для дизайнеров и студий с кейсами на всю ширину.'],
    ['saas-launch', 'SaaS Launch — тема для стартапа', 'themes', 6900, null, 4.8, 198, null, 'Лендинг продукта, тарифы, документация и changelog.'],
    ['minimal-blog', 'Minimal Blog — тема для блога', 'themes', 2900, null, 4.5, 64, null, 'Читабельная типографика, тёмная тема и мгновенная загрузка.'],
    ['fasty-seo', 'FastY SEO — плагин SEO и схемы', 'plugins', 3900, null, 4.9, 870, 'Хит', 'Мета-теги, sitemap, Schema.org и хлебные крошки без лишних запросов.'],
    ['form-forge', 'Form Forge — конструктор форм 152-ФЗ', 'plugins', 4500, 3600, 4.8, 402, null, 'Формы с раздельными согласиями, журналом и хранением в РФ.'],
    ['cache-turbo', 'Cache Turbo — ускорение сайта', 'plugins', 5500, null, 4.7, 233, null, 'Страничный кеш, критический CSS и отложенные скрипты.'],
    ['booking-kit', 'Booking Kit — онлайн-запись', 'plugins', 7900, null, 4.6, 18, 'Новинка', 'Слоты, напоминания и оплата брони для услуг и событий.'],
];
/** A tiny demo archive as an attachment; the downloads module moves it into the private dir. */
function wpfasty_demo_zip(string $slug): ?int
{
    $found = get_posts(['post_type' => 'attachment', 'name' => 'download-' . $slug, 'fields' => 'ids', 'post_status' => 'any', 'numberposts' => 1]);
    if ($found) {
        return (int) $found[0];
    }
    $upload = wp_upload_bits($slug . '.zip', null, "PK\x05\x06" . str_repeat("\0", 18));
    if (!empty($upload['error'])) {
        return null;
    }
    $id = wp_insert_attachment(['post_title' => $slug . '.zip', 'post_name' => 'download-' . $slug, 'post_mime_type' => 'application/zip', 'post_status' => 'inherit'], $upload['file']);
    return $id ?: null;
}

$order = 0;
foreach ($items as [$slug, $title, $cat, $price, $sale, $rating, $reviews, $badge, $excerpt]) {
    $id = wpfasty_demo_post($slug, [
        'post_type' => 'product',
        'post_title' => $title,
        'post_excerpt' => $excerpt,
        'post_content' => '<h2>Что внутри</h2><ul><li>Чистый код на UI8Kit, без привязки к конструктору страниц.</li><li>Светлая и тёмная палитры на дизайн-токенах.</li><li>Демо-контент и пошаговая инструкция по установке.</li><li>Совместимость с актуальными WordPress и PHP.</li></ul>',
        'menu_order' => $order++,
        'post_date' => gmdate('Y-m-d H:i:s', time() - $order * DAY_IN_SECONDS * 3),
    ]);
    wp_set_object_terms($id, [$cats[$cat]], 'product_cat');
    // Digital product fields (ModulY types 0.2): license tiers in cents, private download file, real reviews only.
    $base = ($sale ?: $price) * 100;
    $licenses = wp_json_encode([
        ['tier' => 'regular', 'label' => 'Обычная', 'price' => $base],
        ['tier' => 'extended', 'label' => 'Расширенная', 'price' => $base * 5],
    ]);
    $meta = ['_shop_price' => $price * 100, '_shop_sale_price' => $sale ? $sale * 100 : null, '_shop_sku' => strtoupper($slug),
        '_shop_stock' => null, '_shop_rating' => null, '_shop_reviews' => null, '_shop_badge' => $badge,
        '_shop_version' => '1.' . ($order % 5) . '.0', '_shop_requires_wp' => '6.5', '_shop_requires_php' => '8.2',
        '_shop_changelog' => "1.0.0 — первый релиз", '_shop_preview_url' => home_url('/'), '_shop_support_months' => 6,
        '_shop_sales_count' => $reviews, '_shop_licenses' => $licenses,
        '_shop_download_file' => wpfasty_demo_zip($slug)];
    unset($rating);
    foreach ($meta as $key => $value) {
        $value === null ? delete_post_meta($id, $key) : update_post_meta($id, $key, $value);
    }
    $att = wpfasty_demo_image('market-' . $slug, 1280, 720);
    if ($att) {
        set_post_thumbnail($id, $att);
    }
}

$front::ensurePages();
$home = wpfasty_demo_post('home', ['post_type' => 'page', 'post_title' => 'WP FastY', 'post_content' => '']);
update_option('show_on_front', 'page');
update_option('page_on_front', $home);

$legal = getenv('WPFASTY_LEGAL_JSON');
if ($legal && is_file($legal)) {
    foreach ((array) json_decode((string) file_get_contents($legal), true) as $slug => $doc) {
        wpfasty_demo_post($slug, ['post_type' => 'page', 'post_title' => $doc['title'], 'post_content' => $doc['html']]);
    }
}

wpfasty_demo_menu('Primary', 'primary', [
    ['Каталог', get_post_type_archive_link('product')],
    ['Сайты', get_term_link($cats['sites'])],
    ['Темы', get_term_link($cats['themes'])],
    ['Плагины', get_term_link($cats['plugins'])],
    ['Авторы', $front::pageUrl('author')],
]);
flush_rewrite_rules();
wpfasty_demo_log('WP FastY market demo ready.');
