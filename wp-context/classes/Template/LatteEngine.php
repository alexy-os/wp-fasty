<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Engine;
use WPFasty\Template\ContextTypeHelper;

/**
 * Latte template engine implementation
 */
class LatteEngine implements TemplateEngineInterface
{
    private Engine $latte;
    private string $templateDir;
    private ?ContextTypeHelper $typeHelper;
    
    /**
     * Constructor
     *
     * @param string $templateDir Directory with template files
     * @param string $tempDir Directory for compiled templates
     */
    public function __construct(string $templateDir, string $tempDir)
    {
        $this->templateDir = $templateDir;
        
        // Initialize the type helper
        $this->typeHelper = new ContextTypeHelper();
        
        // Ensure template directory exists
        if (!is_dir($templateDir)) {
            throw new \RuntimeException("Template directory not found: {$templateDir}");
        }
        
        // Ensure temp directory exists and is writable
        if (!is_dir($tempDir)) {
            if (!mkdir($tempDir, 0777, true)) {
                throw new \RuntimeException("Failed to create temp directory: {$tempDir}");
            }
        }
        
        if (!is_writable($tempDir)) {
            throw new \RuntimeException("Temp directory is not writable: {$tempDir}");
        }
        
        $this->latte = new Engine();
        $this->latte->setTempDirectory($tempDir);
        
        // Set strict mode for better error reporting
        $this->latte->setStrictTypes(true);
        
        // Add WordPress extension
        $this->latte->addExtension(new WordPressExtension());
        
        // Register types
        $this->registerTypes();
    }
    
    /**
     * Register context types with Latte
     */
    private function registerTypes(): void
    {
        // Check if types file exists
        if ($this->typeHelper && $this->typeHelper->hasTypesFile()) {
            // Include types file to make the types available to IDE
            include_once $this->typeHelper->getTypesPath();
            
            // Add a filter to add type information
            $this->latte->addFilter('withTypes', function ($value, $type) {
                // This doesn't modify the value, just helps IDE with type checking
                return $value;
            });
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function render(string $template, array $context = []): string
    {
        // Add extension if not provided
        if (!str_ends_with($template, '.latte')) {
            $template .= '.latte';
        }
        
        // Check if template is a full path or relative
        $templatePath = $template;
        if (!file_exists($template)) {
            $templatePath = $this->templateDir . '/' . $template;
        }
        
        if (!file_exists($templatePath)) {
            $this->listTemplates(dirname($templatePath));
            return '';
        }
        
        try {
            // Add context type information if available
            if ($this->typeHelper) {
                $contextType = $this->typeHelper->detectContextType($template);
                $context['_contextType'] = $contextType;
            }
            
            // Render template
            $result = $this->latte->renderToString($templatePath, $context);
            return $result;
        } catch (\Throwable $e) {
            error_log('Error rendering template: ' . $e->getMessage());
            error_log($e->getTraceAsString());
            throw $e;
        }
    }
    
    /**
     * List all template files in directory
     */
    private function listTemplates(string $dir): void
    {
        if (!is_dir($dir)) {
            error_log("Cannot list templates: {$dir} is not a directory");
            return;
        }
        
        $files = glob($dir . '/*.latte');
        foreach ($files as $file) {
            error_log("- " . basename($file));
        }
        
        // List subdirectories
        $dirs = glob($dir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $subdir) {
            $this->listTemplates($subdir);
        }
    }
} 