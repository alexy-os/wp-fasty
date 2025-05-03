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
     *     # Display current context types
     *     $ wp fasty context schema types
     * 
     * @param array<string, mixed> $args Command arguments
     * @param array<string, mixed> $assoc_args Command options
     */
    public function updateSchema(array $args, array $assoc_args): void
    {
        if (empty($args)) {
            WP_CLI::error('Command not recognized. Use "wp fasty context schema update" or "wp fasty context schema types"');
            return;
        }

        if ($args[0] === 'update') {
            $this->updateSchemaFile();
        } elseif ($args[0] === 'types') {
            $this->displayTypeInfo();
        } else {
            WP_CLI::error('Command not recognized. Use "wp fasty context schema update" or "wp fasty context schema types"');
        }
    }

    /**
     * Update schema file
     */
    private function updateSchemaFile(): void
    {
        // Trigger the action that updates the schema
        do_action('wpfasty_update_context_schema');
        
        // Check if the file was created
        $schemaFile = get_template_directory() . '/context.schema.json';
        $typesFile = get_template_directory() . '/context.types.php';
        
        if (file_exists($schemaFile)) {
            $schema = json_decode(file_get_contents($schemaFile), true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                WP_CLI::success('Context schema has been updated successfully.');
                
                // Show a summary of the schema
                WP_CLI::log('Schema contains the following keys:');
                foreach (array_keys($schema) as $key) {
                    WP_CLI::log('- ' . $key);
                }
                
                if (file_exists($typesFile)) {
                    WP_CLI::success('PHP type definitions have been generated successfully.');
                    WP_CLI::log('Type file: ' . $typesFile);
                } else {
                    WP_CLI::error('Failed to create PHP type definitions file.');
                }
            } else {
                WP_CLI::error('Schema file was created but contains invalid JSON.');
            }
        } else {
            WP_CLI::error('Failed to create the schema file.');
        }
    }

    /**
     * Display type information
     */
    private function displayTypeInfo(): void
    {
        $typesFile = get_template_directory() . '/context.types.php';
        
        if (!file_exists($typesFile)) {
            WP_CLI::error('Types file does not exist. Run "wp fasty context schema update" first.');
            return;
        }
        
        // Get the file content
        $content = file_get_contents($typesFile);
        
        // Extract interface names using a regex
        preg_match_all('/interface\s+(\w+)\s+extends\s+Context/i', $content, $matches);
        
        if (!empty($matches[1])) {
            WP_CLI::success('Found the following context types:');
            
            foreach ($matches[1] as $interface) {
                WP_CLI::log('- ' . $interface);
            }
            
            WP_CLI::log("\nUse @import statement in your templates to include types:");
            WP_CLI::log('@import \\WPFasty\\Types\\[TypeName]');
        } else {
            WP_CLI::warning('No context types found in the types file.');
        }
    }
} 