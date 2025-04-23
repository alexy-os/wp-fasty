<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Engine;

/**
 * Latte template engine implementation
 */
class LatteEngine implements TemplateEngineInterface
{
    private Engine $latte;
    private string $templateDir;
    
    /**
     * Constructor
     *
     * @param string $templateDir Directory with template files
     * @param string $tempDir Directory for compiled templates
     */
    public function __construct(string $templateDir, string $tempDir)
    {
        $this->templateDir = $templateDir;
        
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
        
        error_log('LatteEngine initialized with template dir: ' . $templateDir);
        error_log('LatteEngine initialized with temp dir: ' . $tempDir);
        
        // List available templates
        $this->listTemplates($templateDir);
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
        
        error_log('Attempting to render template: ' . $templatePath);
        error_log('Template exists: ' . (file_exists($templatePath) ? 'yes' : 'no'));
        error_log('Context: ' . json_encode($context));
        
        if (!file_exists($templatePath)) {
            error_log("Template not found: {$templatePath}");
            error_log("Available templates in directory:");
            $this->listTemplates(dirname($templatePath));
            return '';
        }
        
        try {
            // Check template file permissions
            error_log('Template permissions: ' . substr(sprintf('%o', fileperms($templatePath)), -4));
            error_log('Template readable: ' . (is_readable($templatePath) ? 'yes' : 'no'));
            
            // Render template
            $result = $this->latte->renderToString($templatePath, $context);
            error_log('Template rendered successfully');
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
        
        error_log("Listing templates in: {$dir}");
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