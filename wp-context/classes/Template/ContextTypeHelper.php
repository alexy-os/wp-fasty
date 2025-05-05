<?php

declare(strict_types=1);

namespace WPFasty\Template;

/**
 * Helper for context type integration with templates
 */
class ContextTypeHelper
{
    /**
     * Path to the types file
     *
     * @var string
     */
    private string $typesPath;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->typesPath = get_template_directory() . '/context.types.php';
    }

    /**
     * Generate a PHPDoc import statement for a template
     *
     * @param string $template The template name
     * @param string $contextType The context type to import
     * @return string The PHPDoc import statement
     */
    public function generateImport(string $template, string $contextType): string
    {
        // Check if types file exists
        if (!file_exists($this->typesPath)) {
            return ''; // No types file, no import
        }

        // Get the context type class
        $contextTypeClass = '\\WPFasty\\Types\\' . ucfirst($contextType) . 'Context';
        
        // Build the import statement
        $importStatement = "/**\n";
        $importStatement .= " * Template: {$template}\n";
        $importStatement .= " * @import {$contextTypeClass}\n";
        $importStatement .= " */\n";
        
        return $importStatement;
    }

    /**
     * Auto-detect context type for a template
     *
     * @param string $template The template name
     * @return string The detected context type
     */
    public function detectContextType(string $template): string
    {
        // Extract template name without path or extension
        $templateName = basename($template, '.latte');
        
        // Map template names to context types
        $contextMap = [
            'front-page' => 'page',
            'home' => 'page',
            'page' => 'page',
            'single' => 'page',
            'archive' => 'archive',
            'category' => 'archive',
            'tag' => 'archive',
            'author' => 'archive',
            'search' => 'archive',
        ];
        
        return $contextMap[$templateName] ?? 'page';
    }

    /**
     * Create a PHPDoc helper for a template
     *
     * @param string $template Template name
     * @return string PHPDoc helper content
     */
    public function createHelperDoc(string $template): string
    {
        $contextType = $this->detectContextType($template);
        return $this->generateImport($template, $contextType);
    }
    
    /**
     * Check if the context.types.php file exists
     *
     * @return bool True if file exists, false otherwise
     */
    public function hasTypesFile(): bool
    {
        return file_exists($this->typesPath);
    }
    
    /**
     * Get path to context.types.php file
     *
     * @return string Path to types file
     */
    public function getTypesPath(): string
    {
        return $this->typesPath;
    }
} 