<?php
namespace WPFasty\Hooks;

class PageTemplateHooks extends AbstractHooks {
    private const DEV_MODE_META_KEY = '_wp_fasty_dev_mode';
    
    public function register(): void {
        // Add metabox only for page post type
        $this->addAction('add_meta_boxes_page', 'addDevModeMetabox');
        $this->addAction('save_post_page', 'saveDevModeMetabox');
        
        // Add early init hook to setup template styles
        $this->addAction('template_redirect', 'setupTemplateStyles', 5);
    }

    public function setupTemplateStyles(): void {
        if (!is_page() || !is_page_template('full-page-template.php')) {
            return;
        }

        $post_id = get_the_ID();
        $is_dev_mode = get_post_meta($post_id, self::DEV_MODE_META_KEY, true);
        $header_hooks = $this->container->get('hooks.header');

        // Remove all possible style outputs first
        remove_action('wp_head', [$header_hooks, 'outputTailwindStyles'], 4);
        remove_action('wp_head', [$header_hooks, 'outputCustomStyles'], 4);
        remove_action('wp_head', [$header_hooks, 'outputTailwindConfig'], 3);

        if ($is_dev_mode === '1') {
            // DEV mode
            add_action('wp_head', [$header_hooks, 'outputCustomStyles'], 4);
            add_action('wp_head', [$header_hooks, 'outputTailwindConfig'], 3);
        } else {
            // PROD mode
            add_action('wp_head', [$header_hooks, 'outputTailwindStyles'], 4);
        }
    }

    public function addDevModeMetabox(): void {
        add_meta_box(
            'wp_fasty_dev_mode',
            __('Development Mode', 'wp-fasty'),
            [$this, 'renderDevModeMetabox'],
            'page',
            'side',
            'core',
            ['__back_compat_meta_box' => true]
        );
    }

    public function renderDevModeMetabox(\WP_Post $post): void {
        wp_nonce_field('wp_fasty_dev_mode', 'wp_fasty_dev_mode_nonce');
        $is_dev_mode = get_post_meta($post->ID, self::DEV_MODE_META_KEY, true);
        $template = get_page_template_slug($post->ID);
        ?>
        <div class="components-panel__row" id="wp-fasty-dev-mode-wrapper" style="<?php echo $template !== 'full-page-template.php' ? 'display: none;' : ''; ?>">
            <div class="components-base-control">
                <div class="components-base-control__field">
                    <label class="components-checkbox-control__label">
                        <input type="checkbox" 
                               name="wp_fasty_dev_mode" 
                               value="1" 
                               <?php checked($is_dev_mode, '1'); ?>>
                        <?php _e('Enable Development Mode', 'wp-fasty'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Enables development mode with live Tailwind configuration.', 'wp-fasty'); ?>
                    </p>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#page_template').on('change', function() {
                    $('#wp-fasty-dev-mode-wrapper').toggle($(this).val() === 'full-page-template.php');
                });
            });
        </script>
        <?php
    }

    public function saveDevModeMetabox(int $post_id): void {
        if (!isset($_POST['wp_fasty_dev_mode_nonce']) || 
            !wp_verify_nonce($_POST['wp_fasty_dev_mode_nonce'], 'wp_fasty_dev_mode')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $is_dev_mode = isset($_POST['wp_fasty_dev_mode']) ? '1' : '0';
        update_post_meta($post_id, self::DEV_MODE_META_KEY, $is_dev_mode);
    }
} 