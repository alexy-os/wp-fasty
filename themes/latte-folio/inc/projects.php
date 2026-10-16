<?php

/**
 * Portfolio site context (work filters, services, next project).
 *
 * @package Latte_Folio
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// CPT, taxonomy, fields, metabox, routing and archive query live in types/project.php
// (registered by the parent WPFasty\Types\Registry).

/**
 * @param array<string, mixed> $context
 * @return array<string, mixed>
 */
add_filter('wpfasty_context', static function (array $context): array {
    $context['labels'] += [
        'view_project' => __('View case study', 'latte-folio'),
        'all_work' => __('All', 'latte-folio'),
        'selected_work' => __('Selected work', 'latte-folio'),
        'visit_site' => __('Visit site', 'latte-folio'),
        'next_project' => __('Next project', 'latte-folio'),
        'contact' => __('Start a project', 'latte-folio'),
    ];

    $filter = wpfasty_term_filter('project_type');
    $context['work'] = [
        'url' => (string) get_post_type_archive_link('project'),
        'types' => $filter['items'],
        'current' => $filter['current'],
    ];

    $contact = get_page_by_path('contact');
    $context['contact_url'] = $contact ? (string) get_permalink($contact) : 'mailto:' . antispambot((string) get_option('admin_email'));

    if (is_front_page()) {
        $context['projects'] = wpfasty_type_posts('project', 6);
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
