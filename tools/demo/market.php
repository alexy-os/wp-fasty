<?php

/**
 * WP FastY marketplace demo (theme wpfasty-market on Latte Shop + ModulY modules): 13 items by 4 demo authors
 * (WordPress users), 7 legal pages with fake ООО «ИТ Гараж» details (tools/demo/legal.json, «Демо-данные»), menus.
 * The home page is a Builder layout (tools/builder/landings/market.json, seeded by the blueprint).
 * Run: wp eval-file tools/demo/market.php  (images from tools/demo/images/market-*.jpg; legal.json next to this file
 * or $WPFASTY_LEGAL_JSON).
 */

require __DIR__ . '/common.php';

wpfasty_demo_cleanup_defaults();
$front = 'LatteShop\\Front';
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

// Demo authors: WordPress users (author archive = common Latte Shop author page).
$authors = [];
foreach ([
    'fasty-studio' => ['FastY Studio', 'Команда WP FastY: готовые сайты и темы на UI8Kit.', '2019-03-01'],
    'nordcode' => ['NordCode', 'Сайты для бизнеса и SaaS, плагины производительности.', '2021-05-01'],
    'pixel-forge' => ['Pixel Forge', 'Дизайн-студия: портфолио, школы и формы.', '2020-09-01'],
    'volga-dev' => ['Volga Dev', 'Небольшая студия из Казани: рестораны, блоги и запись.', '2025-01-15'],
] as $login => [$name, $bio, $since]) {
    $user = get_user_by('login', $login);
    $uid = $user ? $user->ID : wp_insert_user([
        'user_login' => $login, 'user_pass' => wp_generate_password(24), 'user_email' => $login . '@example.com',
        'display_name' => $name, 'role' => 'author', 'user_registered' => $since . ' 00:00:00',
    ]);
    if (!is_wp_error($uid)) {
        wp_update_user(['ID' => $uid, 'display_name' => $name, 'nickname' => $name, 'description' => $bio, 'user_nicename' => $login]);
        $authors[$login] = (int) $uid;
    }
}
$byProduct = ['fasty-agency' => 'fasty-studio', 'fasty-store' => 'fasty-studio', 'clinic-pro' => 'nordcode', 'edu-campus' => 'pixel-forge',
    'restoran' => 'volga-dev', 'latte-magazine' => 'fasty-studio', 'folio-noir' => 'pixel-forge', 'saas-launch' => 'nordcode',
    'minimal-blog' => 'volga-dev', 'fasty-seo' => 'fasty-studio', 'form-forge' => 'pixel-forge', 'cache-turbo' => 'nordcode',
    'booking-kit' => 'volga-dev'];

$order = 0;
foreach ($items as [$slug, $title, $cat, $price, $sale, $rating, $reviews, $badge, $excerpt]) {
    $id = wpfasty_demo_post($slug, [
        'post_type' => 'product',
        'post_title' => $title,
        'post_excerpt' => $excerpt,
        'post_content' => '<h2>Что внутри</h2><ul><li>Чистый код на UI8Kit, без привязки к конструктору страниц.</li><li>Светлая и тёмная палитры на дизайн-токенах.</li><li>Демо-контент и пошаговая инструкция по установке.</li><li>Совместимость с актуальными WordPress и PHP.</li></ul>',
        'menu_order' => $order++,
        'post_date' => gmdate('Y-m-d H:i:s', time() - $order * DAY_IN_SECONDS * 3),
        'post_author' => $authors[$byProduct[$slug] ?? ''] ?? 1,
    ]);
    wp_set_object_terms($id, [$cats[$cat]], 'product_cat');
    // Digital product fields (ModulY types 0.2): license tiers in cents, private download file, real reviews only.
    $base = ($sale ?: $price) * 100;
    $licenses = wp_json_encode([
        ['tier' => 'regular', 'label' => 'Обычная', 'price' => $base],
        ['tier' => 'extended', 'label' => 'Расширенная', 'price' => $base * 5],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $meta = ['_shop_price' => $price * 100, '_shop_sale_price' => $sale ? $sale * 100 : null, '_shop_sku' => strtoupper($slug),
        '_shop_stock' => null, '_shop_rating' => null, '_shop_reviews' => null, '_shop_badge' => $badge,
        '_shop_version' => '1.' . ($order % 5) . '.0', '_shop_requires_wp' => '6.5', '_shop_requires_php' => '8.2',
        '_shop_changelog' => '1.' . ($order % 5) . ".0|2026-10-01|Совместимость с WordPress 7.1, ускорение загрузки\n1.0.0|2026-06-01|Первый релиз", '_shop_preview_url' => home_url('/'), '_shop_support_months' => 6,
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
foreach (['cart' => 'Корзина', 'checkout' => 'Оформление заказа'] as $slug => $title) {
    $page = get_page_by_path($slug);
    if ($page) {
        wp_update_post(['ID' => $page->ID, 'post_title' => $title]);
    }
}
// The home page is a Builder layout (landings/market.json); without the Builder a plain page keeps the front static.
if (!get_page_by_path('home')) {
    $home = wpfasty_demo_post('home', ['post_type' => 'page', 'post_title' => 'WP FastY', 'post_content' => '']);
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home);
}

// Legal documents with clearly fake details (Демо-данные) + seller details for the footer.
$legalFile = getenv('WPFASTY_LEGAL_JSON') ?: __DIR__ . '/legal.json';
$legal = is_file($legalFile) ? (array) json_decode((string) file_get_contents($legalFile), true) : [];
update_option('wpfasty_company', (array) ($legal['company'] ?? []));
$legalLinks = [];
foreach ((array) ($legal['pages'] ?? []) as $slug => $doc) {
    $existing = get_page_by_path($slug);
    $pid = $existing
        ? wp_update_post(['ID' => $existing->ID, 'post_title' => $doc['title'], 'post_content' => $doc['html'], 'post_status' => 'publish'])
        : wpfasty_demo_post($slug, ['post_type' => 'page', 'post_title' => $doc['title'], 'post_content' => $doc['html']]);
    if ($slug === 'privacy-policy') {
        update_option('wp_page_for_privacy_policy', (int) $pid);
    }
    $legalLinks[] = [$doc['title'], (string) get_permalink((int) $pid)];
}
if ($legalLinks !== []) {
    wpfasty_demo_menu('Документы', 'legal', $legalLinks);
}
$author = get_page_by_path('author');
if ($author) {
    wp_delete_post($author->ID, true);
}

wpfasty_demo_menu('Primary', 'primary', [
    ['Каталог', get_post_type_archive_link('product')],
    ['Сайты', get_term_link($cats['sites'])],
    ['Темы', get_term_link($cats['themes'])],
    ['Плагины', get_term_link($cats['plugins'])],
]);
flush_rewrite_rules();
wpfasty_demo_log('WP FastY market demo ready.');
