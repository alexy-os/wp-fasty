<?php
namespace WPFasty\Templates;

class FullPageTemplate {
    public function __construct() {
        add_action('init', [$this, 'registerTemplate']);
    }

    public function registerTemplate(): void {
        add_filter('theme_page_templates', [$this, 'addNewTemplate']);
        add_filter('template_include', [$this, 'loadTemplate']);
    }

    public function addNewTemplate($templates): array {
        $templates['full-page-template.php'] = __('Full Page Template', 'wp-fasty');
        return $templates;
    }

    public function loadTemplate($template) {
        if (is_page_template('full-page-template.php')) {
            $template = plugin_dir_path(__FILE__) . 'full-page-template.php';
        }
        return $template;
    }
} 