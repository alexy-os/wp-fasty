<?php
namespace WPFasty\Templates;

class FullPageTemplate {
    public function __construct() {
        add_action('init', [$this, 'registerTemplate']);
    }

    public function registerTemplate(): void {
        add_filter('theme_page_templates', [$this, 'addNewTemplate']);
        add_filter('template_include', [$this, 'loadTemplate']);
        
        // Disable wpautop for the full page template
        add_filter('the_content', [$this, 'disableWpautopForTemplate'], 5);
    }

    public function addNewTemplate($templates): array {
        $templates['full-page-template.php'] = __('Full Page Template', 'wp-fasty');
        return $templates;
    }

    public function loadTemplate($template) {
        if (is_page_template('full-page-template.php')) {
            $template = get_stylesheet_directory() . '/page-templates/full-page-template.php';
        }
        return $template;
    }
    
    /**
     * Disable wpautop for the full page template
     */
    public function disableWpautopForTemplate($content) {
        if (is_page_template('full-page-template.php')) {
            // Remove all applications of wpautop to the content
            remove_filter('the_content', 'wpautop');
            
            // Return the content without changes
            return $content;
        }
        return $content;
    }
} 