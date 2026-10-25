<?php

require __DIR__ . '/common.php';

wpfasty_demo_cleanup_defaults();
update_option('blogname', 'Alex Rivera');
update_option('blogdescription', 'Available for projects — Q1 2027');

$types = [];
foreach (['Branding', 'Web', 'Product', 'Editorial'] as $name) {
    $t = term_exists($name, 'project_type') ?: wp_insert_term($name, 'project_type');
    $types[$name] = (int) (is_array($t) ? $t['term_id'] : $t);
}

$projects = [
    ['northwind-rebrand', 'Northwind rebrand', 'Branding', 'Northwind Co.', 2026, 'Art direction', 'northwind-mountain'],
    ['atlas-dashboard', 'Atlas analytics dashboard', 'Product', 'Atlas Labs', 2026, 'Product design', 'atlas-desk'],
    ['field-notes-magazine', 'Field Notes magazine', 'Editorial', 'Field Notes', 2025, 'Layout & type', 'field-magazine'],
    ['ember-coffee', 'Ember Coffee e-commerce', 'Web', 'Ember Roasters', 2025, 'Design & build', 'ember-coffee'],
    ['orbit-app', 'Orbit mobile app', 'Product', 'Orbit Health', 2025, 'UX lead', 'orbit-phone'],
    ['kinfolk-studio', 'Kinfolk Studio website', 'Web', 'Kinfolk', 2024, 'WordPress build', 'kinfolk-studio'],
];
$order = 0;
// year: integer (Codex project.year, migrated from the earlier string field); the first two projects are featured
foreach ($projects as [$slug, $title, $type, $client, $year, $role, $img]) {
    $id = wpfasty_demo_post($slug, [
        'post_type' => 'project',
        'post_title' => $title,
        'post_excerpt' => "A focused {$type} engagement for {$client}: research, system design and a calm, fast delivery.",
        'post_content' => wpfasty_demo_lorem(5),
        'menu_order' => $order++,
    ]);
    wp_set_object_terms($id, [$types[$type]], 'project_type');
    update_post_meta($id, '_folio_client', $client);
    update_post_meta($id, '_folio_year', $year);
    update_post_meta($id, '_folio_role', $role);
    update_post_meta($id, '_folio_url', 'https://example.com/' . $slug);
    if ($order <= 2) {
        update_post_meta($id, '_folio_featured', true);
    } else {
        delete_post_meta($id, '_folio_featured');
    }
    $att = wpfasty_demo_image($img, 1600, 1200);
    if ($att) {
        set_post_thumbnail($id, $att);
    }
}

$home = wpfasty_demo_post('home', [
    'post_type' => 'page',
    'post_title' => 'I design calm, fast digital products.',
    'post_excerpt' => 'Independent designer & WordPress developer. I help small teams ship brands, products and websites that feel effortless.',
    'post_content' => '',
]);
$contact = wpfasty_demo_post('contact', ['post_type' => 'page', 'post_title' => 'Contact', 'post_content' => '<!-- wp:paragraph --><p>Tell me about your project: <a href="mailto:hello@example.com">hello@example.com</a>. I usually reply within a day.</p><!-- /wp:paragraph -->']);
$about = wpfasty_demo_post('about', ['post_type' => 'page', 'post_title' => 'About', 'post_content' => wpfasty_demo_lorem(3, false)]);
update_option('show_on_front', 'page');
update_option('page_on_front', $home);

wpfasty_demo_menu('Primary', 'primary', [['Work', get_post_type_archive_link('project')], ['About', get_permalink($about)], ['Contact', get_permalink($contact)]]);
wpfasty_demo_menu('Footer', 'footer', [['Work', get_post_type_archive_link('project')], ['Contact', get_permalink($contact)]]);
flush_rewrite_rules();

wpfasty_demo_log('Latte Folio demo content ready.');
