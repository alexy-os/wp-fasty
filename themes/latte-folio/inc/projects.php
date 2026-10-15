<?php

/**
 * Projects: CPT, taxonomy, meta (with nonce + sanitization), view routing and context.
 *
 * @package Latte_Folio
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

const LATTE_FOLIO_META = [
    'client' => 'Client',
    'year' => 'Year',
    'role' => 'Role',
    'url' => 'Live URL',
];

add_action('init', static function (): void {
    register_post_type('project', [
        'labels' => [
            'name' => __('Projects', 'latte-folio'),
            'singular_name' => __('Project', 'latte-folio'),
            'add_new_item' => __('Add project', 'latte-folio'),
            'edit_item' => __('Edit project', 'latte-folio'),
        ],
        'public' => true,
        'has_archive' => 'work',
        'rewrite' => ['slug' => 'work/project', 'with_front' => false],
        'menu_icon' => 'dashicons-portfolio',
        'show_in_rest' => true,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
    ]);

    register_taxonomy('project_type', 'project', [
        'labels' => ['name' => __('Project types', 'latte-folio'), 'singular_name' => __('Project type', 'latte-folio')],
        'public' => true,
        'hierarchical' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'rewrite' => ['slug' => 'work/type', 'with_front' => false],
    ]);

    foreach (array_keys(LATTE_FOLIO_META) as $key) {
        register_post_meta('project', "_folio_{$key}", [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
            'sanitize_callback' => $key === 'url' ? 'esc_url_raw' : 'sanitize_text_field',
            'auth_callback' => static fn (): bool => current_user_can('edit_posts'),
        ]);
    }
});

add_action('add_meta_boxes_project', static function (): void {
    add_meta_box('latte-folio-details', __('Project details', 'latte-folio'), static function (WP_Post $post): void {
        wp_nonce_field('latte_folio_save', 'latte_folio_nonce');
        foreach (LATTE_FOLIO_META as $key => $label) {
            $value = (string) get_post_meta($post->ID, "_folio_{$key}", true);
            printf(
                '<p><label for="folio-%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="%3$s" id="folio-%1$s" name="folio[%1$s]" value="%4$s"></p>',
                esc_attr($key),
                esc_html($label),
                $key === 'url' ? 'url' : 'text',
                esc_attr($value)
            );
        }
    }, 'project', 'side');
});

add_action('save_post_project', static function (int $postId): void {
    if (!isset($_POST['latte_folio_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['latte_folio_nonce'])), 'latte_folio_save')
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || !current_user_can('edit_post', $postId)
    ) {
        return;
    }
    $input = isset($_POST['folio']) && is_array($_POST['folio']) ? wp_unslash($_POST['folio']) : [];
    foreach (array_keys(LATTE_FOLIO_META) as $key) {
        $raw = isset($input[$key]) ? (string) $input[$key] : '';
        $clean = $key === 'url' ? esc_url_raw($raw) : sanitize_text_field($raw);
        update_post_meta($postId, "_folio_{$key}", $clean);
    }
});

/**
 * Project view-model: meta + types.
 *
 * @param array<string, mixed> $data
 * @return array<string, mixed>
 */
add_filter('wpfasty_post_data', static function (array $data, WP_Post $post): array {
    if ($post->post_type !== 'project') {
        return $data;
    }
    $data['project'] = [];
    foreach (LATTE_FOLIO_META as $key => $label) {
        $value = (string) get_post_meta($post->ID, "_folio_{$key}", true);
        if ($value !== '') {
            $data['project'][$key] = ['label' => $label, 'value' => $value];
        }
    }
    $data['types'] = [];
    foreach (get_the_terms($post, 'project_type') ?: [] as $term) {
        $data['types'][] = ['name' => $term->name, 'slug' => $term->slug, 'url' => (string) get_term_link($term)];
    }
    return $data;
}, 10, 2);

add_filter('wpfasty_resolve_view', static function (string $view): string {
    if (is_post_type_archive('project') || is_tax('project_type')) {
        return 'pages/work';
    }
    if (is_singular('project')) {
        return 'pages/project';
    }
    return $view;
});

add_action('pre_get_posts', static function (WP_Query $q): void {
    if (!is_admin() && $q->is_main_query() && ($q->is_post_type_archive('project') || $q->is_tax('project_type'))) {
        $q->set('posts_per_page', 12);
        $q->set('orderby', ['menu_order' => 'ASC', 'date' => 'DESC']);
    }
});

/**
 * @param array<string, mixed> $context
 * @return array<string, mixed>
 */
add_filter('wpfasty_context', static function (array $context): array {
    // Portfolio is full-width: no content sidebar.
    $context['widgets']['sidebar'] = '';

    $context['labels'] += [
        'view_project' => __('View case study', 'latte-folio'),
        'all_work' => __('All', 'latte-folio'),
        'selected_work' => __('Selected work', 'latte-folio'),
        'visit_site' => __('Visit site', 'latte-folio'),
        'next_project' => __('Next project', 'latte-folio'),
        'contact' => __('Start a project', 'latte-folio'),
    ];

    $context['work'] = [
        'url' => (string) get_post_type_archive_link('project'),
        'types' => [],
        'current' => is_tax('project_type') ? (string) get_queried_object()->slug : '',
    ];
    foreach (get_terms(['taxonomy' => 'project_type', 'hide_empty' => true]) ?: [] as $term) {
        if ($term instanceof WP_Term) {
            $context['work']['types'][] = ['name' => $term->name, 'slug' => $term->slug, 'url' => (string) get_term_link($term)];
        }
    }

    $contact = get_page_by_path('contact');
    $context['contact_url'] = $contact ? (string) get_permalink($contact) : 'mailto:' . antispambot((string) get_option('admin_email'));

    if (is_front_page() && function_exists('wpfasty_posts')) {
        $context['projects'] = wpfasty_posts(get_posts([
            'post_type' => 'project',
            'numberposts' => 6,
            'orderby' => ['menu_order' => 'ASC', 'date' => 'DESC'],
        ]));
        $context['services'] = [
            ['icon' => 'layers', 'title' => __('Product design', 'latte-folio'), 'text' => __('Flows, systems and interfaces that scale on an 8px grid.', 'latte-folio')],
            ['icon' => 'sparkles', 'title' => __('Brand & identity', 'latte-folio'), 'text' => __('Logos, type and color tokens that travel across every surface.', 'latte-folio')],
            ['icon' => 'briefcase', 'title' => __('WordPress builds', 'latte-folio'), 'text' => __('Fast Latte themes on UI8Kit primitives, no page builders.', 'latte-folio')],
        ];
    }

    if (is_singular('project')) {
        $next = get_adjacent_post(false, '', false) ?: (get_posts(['post_type' => 'project', 'numberposts' => 1, 'order' => 'ASC'])[0] ?? null);
        $context['next_project'] = $next instanceof WP_Post && $next->ID !== get_the_ID() && function_exists('wpfasty_posts')
            ? (wpfasty_posts([$next])[0] ?? null)
            : null;
    }

    return $context;
});
