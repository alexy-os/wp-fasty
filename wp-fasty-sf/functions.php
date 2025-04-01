<?php
/**
 * WP Fasty Storefront Child Theme functions
 */

// Disable parent theme styles and add our own
function wp_fasty_sf_enqueue_styles() {
    // Disable parent theme styles
    wp_dequeue_style('storefront-style');
    wp_dequeue_style('storefront-woocommerce-style');
    wp_dequeue_style('storefront-gutenberg-blocks');
    
    // Enqueue our own styles
    wp_enqueue_style(
        'wp-fasty-sf-style',
        get_stylesheet_directory_uri() . '/css/theme.min.css',
        array(),
        wp_get_theme()->get('Version')
    );
}
add_action('wp_enqueue_scripts', 'wp_fasty_sf_enqueue_styles', 20);

// Disable inline styles
function wp_fasty_sf_remove_inline_styles() {
    remove_action('wp_enqueue_scripts', 'storefront_add_customizer_css', 130);
}
add_action('init', 'wp_fasty_sf_remove_inline_styles');

// Disable inline styles for Gutenberg blocks
function wp_fasty_sf_remove_gutenberg_inline_styles() {
    wp_dequeue_style('storefront-gutenberg-blocks-inline');
}
add_action('wp_enqueue_scripts', 'wp_fasty_sf_remove_gutenberg_inline_styles', 20);

// Disable unnecessary Storefront scripts (optional)
function wp_fasty_sf_dequeue_scripts() {
    wp_dequeue_script('storefront-header-cart');
}
add_action('wp_enqueue_scripts', 'wp_fasty_sf_dequeue_scripts', 20);

// Add base classes for the body
function wp_fasty_sf_body_classes($classes) {
    $classes[] = 'font-sans';
    $classes[] = 'bg-background';
    $classes[] = 'text-foreground';
    $classes[] = 'antialiased';
    return $classes;
}
add_filter('body_class', 'wp_fasty_sf_body_classes');

// Add dark mode toggle to header
function wp_fasty_sf_add_header_element() {
    echo '<div class="custom-header-element"><button id="dark-mode-toggle" class="dark-mode-toggle-btn" title="Toggle dark mode">🌓</button></div>';
}
add_action('storefront_header', 'wp_fasty_sf_add_header_element', 39);

// Add dark mode toggle script to footer
function wp_fasty_sf_add_dark_mode_script() {
    ?>
    <script>
        const darkModeToggle = document.getElementById('dark-mode-toggle');
        
        // Check for saved theme preference or use the system preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        // Add click listener to toggle
        darkModeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            
            // Save preference
            if (document.documentElement.classList.contains('dark')) {
                localStorage.theme = 'dark';
            } else {
                localStorage.theme = 'light';
            }
        });
    </script>
    <?php
}
add_action('wp_footer', 'wp_fasty_sf_add_dark_mode_script');

// Add inline CSS for header elements and dark mode toggle
function wp_fasty_sf_header_inline_css() {
    ?>
    <style>
        .site-header .col-full {
            display: flex;
            align-items: center;
        }
        .site-search {
            margin-bottom: 0 !important;
        }
        .custom-header-element {
            display: inline-flex;
            align-items: center;
            margin-left: 1rem;
        }
        .dark-mode-toggle-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem;
            border-radius: 9999px;
            transition: transform 0.2s ease;
        }
        .dark-mode-toggle-btn:hover {
            transform: scale(1.1);
            background-color: hsl(var(--secondary));
        }
        @media (max-width: 768px) {
            .site-search, .custom-header-element {
                margin: 0;
                padding: 0;
                flex: 1;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'wp_fasty_sf_header_inline_css'); 