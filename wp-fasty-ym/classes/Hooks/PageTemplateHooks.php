<?php

declare(strict_types=1);

/**
 * Page template hooks
 *
 * @package WPFasty\Hooks
 */

namespace WPFasty\Hooks;

class PageTemplateHooks extends AbstractHooks
{
    public function register(): void
    {
        // Simple mode - just register the template
        $this->addAction('template_redirect', 'setupTemplate', 5);
    }

    public function setupTemplate(): void
    {
        // Check if we are on a page with our template
        if (is_page() && is_page_template('full-page-template.php')) {
            // Add classes for using theme styles
            add_filter('body_class', function ($classes) {
                $classes[] = 'wp-fasty-full-page';
                return $classes;
            });
        }
    }
}
