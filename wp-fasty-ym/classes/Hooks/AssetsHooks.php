<?php
namespace WPFasty\Hooks;

class AssetsHooks extends AbstractHooks {
    public function register(): void {
        $this->addAction('wp_enqueue_scripts', 'enqueueStyles');
        // $this->addAction('wp_enqueue_scripts', 'enqueueScripts');
        
        // Filter for style tags HTML modification
        add_filter('style_loader_tag', [$this, 'fixStyleTags'], 10, 4);
        
        // Filter for style URLs
        add_filter('style_loader_src', [$this, 'modifyStyleUrl'], 10, 2);
        
        // Copy styles on theme activation
        add_action('after_switch_theme', [$this, 'copyStylesToRoot']);
        
        // Clean up on theme deactivation
        add_action('switch_theme', [$this, 'cleanupRootStyles']);
    }

    /**
     * Modifies style tags to ensure consistent double quotes and proper HTML structure
     * 
     * @param string $html Style tag HTML
     * @param string $handle Style identifier
     * @param string $href Style URL
     * @param string $media Media query
     * @return string Modified HTML tag
     */
    public function fixStyleTags(string $html, string $handle, string $href, string $media): string {
        // Create a new link element with proper attributes
        $attributes = [
            'rel' => 'stylesheet',
            'id' => $handle . '-css',
            'href' => $href,
            'type' => 'text/css',
            'media' => $media
        ];

        $tag = '<link';
        foreach ($attributes as $key => $value) {
            if (!empty($value)) {
                $tag .= sprintf(' %s="%s"', esc_attr($key), esc_attr($value));
            }
        }
        $tag .= '>';

        return $tag;
    }

    /**
     * Modifies style URLs to point to the site root
     * 
     * @param string $src Original style URL
     * @param string $handle Style handle
     * @return string Modified URL
     */
    public function modifyStyleUrl(string $src, string $handle): string {
        if ($handle === 'wp-fasty-style') {
            return home_url('/style.css');
        }
        if ($handle === 'wp-fasty-ym-tailwind') {
            return home_url('/theme.min.css');
        }
        return $src;
    }

    /**
     * Copies style files to the site root directory
     * Should be called on theme activation
     */
    public function copyStylesToRoot(): void {
        $wp_upload_dir = wp_upload_dir();
        $root_dir = ABSPATH;

        // Copy main style
        $style_source = get_template_directory() . '/style.css';
        $style_dest = $root_dir . 'style.css';
        $this->copyStyleFile($style_source, $style_dest);

        // Copy Tailwind style
        $tailwind_source = get_template_directory() . '/theme.min.css';
        $tailwind_dest = $root_dir . 'theme.min.css';
        $this->copyStyleFile($tailwind_source, $tailwind_dest);
    }

    /**
     * Helper method to copy a style file
     * 
     * @param string $source Source file path
     * @param string $destination Destination file path
     */
    private function copyStyleFile(string $source, string $destination): void {
        if (!file_exists($source)) {
            error_log("Source style file not found: {$source}");
            return;
        }

        if (!copy($source, $destination)) {
            error_log("Failed to copy style file from {$source} to {$destination}");
            return;
        }

        // Set proper permissions
        chmod($destination, 0644);
    }

    public function enqueueStyles(): void {
        // Enqueue main theme style
        wp_enqueue_style(
            'wp-fasty-style',
            get_stylesheet_uri(),
            [],
            wp_get_theme()->get('Version')
        );

        // Enqueue Tailwind styles
        wp_enqueue_style(
            'wp-fasty-ym-tailwind',
            get_template_directory_uri() . '/theme.min.css',
            ['wp-fasty-style'], // Depends on main style
            wp_get_theme()->get('Version')
        );

        // Add inline styles for theme customization
        /*wp_add_inline_style(
            'wp-fasty-ym-tailwind',
            $this->getCustomStyles()
        );*/
    }

    /**
     * Get custom styles based on theme customization
     * 
     * @return string Custom CSS
     */
    /*private function getCustomStyles(): string {
        $custom_css = '';
        
        // Add any dynamic styles here
        if (get_theme_mod('custom_primary_color')) {
            $custom_css .= sprintf(
                ':root { --primary: %s; }',
                esc_attr(get_theme_mod('custom_primary_color'))
            );
        }

        return $custom_css;
    }*/

    /*public function enqueueScripts(): void {
        $script_path = get_template_directory() . '/assets/js/navigation.js';
        if (!file_exists($script_path)) {
            error_log('Navigation script not found at: ' . $script_path);
            return;
        }
        
        wp_enqueue_script(
            'wp-fasty-navigation',
            get_template_directory_uri() . '/assets/js/navigation.js',
            [],
            wp_get_theme()->get('Version'),
            true
        );
    }*/

    /**
     * Removes style files from root directory on theme deactivation
     */
    public function cleanupRootStyles(): void {
        $root_dir = ABSPATH;
        $files_to_remove = [
            $root_dir . 'style.css',
            $root_dir . 'theme.min.css'
        ];

        foreach ($files_to_remove as $file) {
            if (file_exists($file) && is_writable($file)) {
                if (!unlink($file)) {
                    error_log("Failed to remove style file: {$file}");
                }
            }
        }
    }
} 