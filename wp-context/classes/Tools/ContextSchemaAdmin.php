<?php

declare(strict_types=1);

namespace WPFasty\Tools;

use WPFasty\Core\BootableServiceInterface;
use WPFasty\Core\ContainerInterface;

/**
 * Admin interface for context schema management
 */
class ContextSchemaAdmin implements BootableServiceInterface
{
    /**
     * Service container
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * Constructor
     *
     * @param ContainerInterface $container Service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * Boot the service
     */
    public function boot(): void
    {
        add_action('admin_menu', [$this, 'registerAdminPage']);
        add_action('admin_init', [$this, 'handleAdminActions']);
    }
    
    /**
     * Register admin page
     */
    public function registerAdminPage(): void
    {
        add_submenu_page(
            'tools.php',                       // Parent menu slug
            'Context Schema',                  // Page title
            'Context Schema',                  // Menu title
            'manage_options',                  // Capability
            'wpfasty-context-schema',          // Menu slug
            [$this, 'renderAdminPage']         // Callback
        );
    }
    
    /**
     * Handle admin actions
     */
    public function handleAdminActions(): void
    {
        if (isset($_GET['page']) && $_GET['page'] === 'wpfasty-context-schema') {
            // Check for update action
            if (isset($_POST['wpfasty_update_schema']) && current_user_can('manage_options')) {
                // Verify nonce
                check_admin_referer('wpfasty_update_schema_nonce');
                
                // Trigger the update action
                do_action('wpfasty_update_context_schema');
                
                // Redirect to avoid form resubmission
                wp_redirect(add_query_arg(['updated' => 'true'], admin_url('tools.php?page=wpfasty-context-schema')));
                exit;
            }
        }
    }
    
    /**
     * Render admin page
     */
    public function renderAdminPage(): void
    {
        $schemaPath = get_template_directory() . '/context.schema.json';
        $schemaExists = file_exists($schemaPath);
        $schemaData = [];
        
        if ($schemaExists) {
            $schemaContent = file_get_contents($schemaPath);
            $schemaData = json_decode($schemaContent, true);
        }
        
        ?>
        <div class="wrap">
            <h1>WP Fasty Context Schema</h1>
            
            <?php if (isset($_GET['updated']) && $_GET['updated'] === 'true') : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Context schema has been updated successfully.', 'wp-fasty'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php _e('Update Schema', 'wp-fasty'); ?></h2>
                <p><?php _e('This will generate or update the context.schema.json file that defines the structure of data available in templates.', 'wp-fasty'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('wpfasty_update_schema_nonce'); ?>
                    <p>
                        <button type="submit" name="wpfasty_update_schema" class="button button-primary"><?php _e('Update Context Schema', 'wp-fasty'); ?></button>
                    </p>
                </form>
            </div>
            
            <?php if ($schemaExists && !empty($schemaData)) : ?>
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Current Schema', 'wp-fasty'); ?></h2>
                    <p><?php _e('Last updated: ', 'wp-fasty'); ?> <?php echo date('F j, Y, g:i a', filemtime($schemaPath)); ?></p>
                    
                    <div style="background: #f9f9f9; padding: 15px; overflow: auto; max-height: 500px;">
                        <pre><?php echo esc_html(json_encode($schemaData, JSON_PRETTY_PRINT)); ?></pre>
                    </div>
                </div>
            <?php elseif (!$schemaExists) : ?>
                <div class="card" style="margin-top: 20px;">
                    <h2><?php _e('Schema Not Found', 'wp-fasty'); ?></h2>
                    <p><?php _e('No context schema file exists yet. Click the button above to generate it.', 'wp-fasty'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
} 