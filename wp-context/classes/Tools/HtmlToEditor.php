<?php

namespace WPFasty\Tools;

use WPFasty\Hooks\AbstractHooks;

class HtmlToEditor extends AbstractHooks {
    public static function convert($html) {
        // Method implementation
    }
    
    /**
     * Disabling wpautop for the full page template
     */
    public function disable_wpautop_for_full_page() {
        if (is_page_template('full-page-template.php')) {
            remove_filter('the_content', 'wpautop');
            remove_filter('the_excerpt', 'wpautop');
        }
    }
    
    /**
     * Disabling formatting for shortcodes
     */
    public function restore_html_entities($content) {
        $raw_content = $content;
        // Restoring HTML comments
        $raw_content = str_replace('&lt;!--', '<!--', $raw_content);
        $raw_content = str_replace('--&gt;', '-->', $raw_content);
        // Restoring CDATA
        $raw_content = str_replace('&lt;![CDATA[', '<![CDATA[', $raw_content);
        $raw_content = str_replace(']]&gt;', ']]>', $raw_content);
        return $raw_content;
    }
    
    /**
     * Disabling autoformatting when saving a post
     */
    public function disable_autop_on_save($content) {
        remove_filter('content_save_pre', 'wpautop');
        return $content;
    }
    
    /**
     * Adding support for the "Text" mode in the classic editor
     */
    public function allow_full_html_in_editor() {
        global $allowedposttags;
        $allowedposttags['html'] = array(
            'lang' => true,
            'class' => true
        );
        $allowedposttags['head'] = array(
            'profile' => true
        );
        $allowedposttags['style'] = array(
            'type' => true
        );
        $allowedposttags['script'] = array(
            'type' => true,
            'src' => true
        );
        $allowedposttags['meta'] = array(
            'charset' => true,
            'name' => true,
            'content' => true,
            'http-equiv' => true
        );
    }
    
    /**
     * Register all hooks and filters
     */
    public function register(): void {
        // Disabling wpautop for the full page template
        $this->addAction('wp', 'disable_wpautop_for_full_page');
        
        // Disabling wpautop for all pages
        remove_filter('the_content', 'wpautop');
        remove_filter('the_excerpt', 'wpautop');
        
        // Disabling conversion of \n to <br>
        remove_filter('the_content', 'nl2br', 10);
        
        // Disabling wptexturize, which replaces quotes and other characters
        remove_filter('the_content', 'wptexturize');
        
        // Disabling autoformatting for widgets
        remove_filter('widget_text_content', 'wpautop');
        
        // Restoring HTML entities
        $this->addFilter('the_content', 'restore_html_entities', 0);
        
        // Disabling autoformatting when saving a post
        $this->addFilter('content_save_pre', 'disable_autop_on_save', 0);
        
        // Adding support for full HTML in editor
        $this->addAction('init', 'allow_full_html_in_editor');
    }
}

// Initialize the class
add_action('plugins_loaded', ['WPFasty\Tools\HtmlToEditor', 'init']); 