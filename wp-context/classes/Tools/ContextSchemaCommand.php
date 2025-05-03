<?php

declare(strict_types=1);

namespace WPFasty\Tools;

use WPFasty\Core\BootableServiceInterface;
use WP_CLI;
use WP_Error;

/**
 * Command line tool for updating context schema
 */
class ContextSchemaCommand implements BootableServiceInterface
{
    /**
     * Boot the service
     */
    public function boot(): void
    {
        // Check if WP-CLI is available
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('fasty context schema', [$this, 'updateSchema']);
        }
    }
    
    /**
     * Update the context schema file
     * 
     * ## EXAMPLES
     * 
     *     # Update the context schema
     *     $ wp fasty context schema update
     * 
     * @param array<string, mixed> $args Command arguments
     * @param array<string, mixed> $assoc_args Command options
     */
    public function updateSchema(array $args, array $assoc_args): void
    {
        if (empty($args) || $args[0] !== 'update') {
            WP_CLI::error('Command not recognized. Use "wp fasty context schema update"');
            return;
        }
        
        // Trigger the action that updates the schema
        do_action('wpfasty_update_context_schema');
        
        // Check if the file was created
        $schemaFile = get_template_directory() . '/context.json';
        if (file_exists($schemaFile)) {
            $schema = json_decode(file_get_contents($schemaFile), true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                WP_CLI::success('Context schema has been updated successfully.');
                
                // Show a summary of the schema
                WP_CLI::log('Schema contains the following keys:');
                foreach (array_keys($schema) as $key) {
                    WP_CLI::log('- ' . $key);
                }
            } else {
                WP_CLI::error('Schema file was created but contains invalid JSON.');
            }
        } else {
            WP_CLI::error('Failed to create the schema file.');
        }
    }
} 