<?php
/**
 * Assets Service
 * Handles theme assets loading (styles and scripts)
 */

namespace FastyChild\Services;

use FastyChild\Core\Application;

class AssetsService {
    /**
     * Application instance
     * @var Application
     */
    private $app;
    
    /**
     * Constructor
     * 
     * @param Application $app
     */
    public function __construct(Application $app) {
        $this->app = $app;
    }
    
    /**
     * Enqueue theme styles
     * 
     * @return void
     */
    public function enqueueStyles(): void {
        // Get styles from configuration
        $styles = $this->app->config('assets.styles', []);
        
        foreach ($styles as $handle => $style) {
            if (is_string($style)) {
                // If string only - it's just a path to file
                wp_enqueue_style(
                    "fasty-child-{$handle}", 
                    FASTY_CHILD_URI . $style, 
                    [], 
                    $this->getFileVersion(FASTY_CHILD_PATH . $style)
                );
            } elseif (is_array($style)) {
                // If array - it contains additional parameters
                $src = $style['src'] ?? '';
                $deps = $style['deps'] ?? [];
                $ver = isset($style['ver']) ? $style['ver'] : $this->getFileVersion(FASTY_CHILD_PATH . $src);
                $media = $style['media'] ?? 'all';
                
                wp_enqueue_style(
                    "fasty-child-{$handle}", 
                    FASTY_CHILD_URI . $src, 
                    $deps, 
                    $ver,
                    $media
                );
            }
        }
    }
    
    /**
     * Enqueue theme scripts
     * 
     * @return void
     */
    public function enqueueScripts(): void {
        // Load scripts from configuration
        $scripts = $this->app->config('assets.scripts', []);
        
        foreach ($scripts as $handle => $script) {
            if (is_string($script)) {
                wp_enqueue_script(
                    "fasty-child-{$handle}", 
                    FASTY_CHILD_URI . $script, 
                    ['jquery'], 
                    $this->getFileVersion(FASTY_CHILD_PATH . $script), 
                    true
                );
            } elseif (is_array($script)) {
                $src = $script['src'] ?? '';
                $deps = $script['deps'] ?? ['jquery'];
                $ver = isset($script['ver']) ? $script['ver'] : $this->getFileVersion(FASTY_CHILD_PATH . $src);
                $in_footer = $script['in_footer'] ?? true;
                
                wp_enqueue_script(
                    "fasty-child-{$handle}", 
                    FASTY_CHILD_URI . $src, 
                    $deps, 
                    $ver, 
                    $in_footer
                );
                
                // If script has localization
                if (isset($script['localize'])) {
                    wp_localize_script(
                        "fasty-child-{$handle}", 
                        $script['localize']['object_name'], 
                        $script['localize']['data']
                    );
                }
            }
        }
    }
    
    /**
     * Enqueue admin styles
     * 
     * @return void
     */
    public function enqueueAdminStyles(): void {
        // Load admin styles if defined
        $admin_styles = $this->app->config('assets.admin_styles', []);
        
        foreach ($admin_styles as $handle => $style) {
            if (is_string($style)) {
                wp_enqueue_style(
                    "fasty-child-admin-{$handle}", 
                    FASTY_CHILD_URI . $style, 
                    [], 
                    $this->getFileVersion(FASTY_CHILD_PATH . $style)
                );
            } elseif (is_array($style)) {
                // Parameters similar to main styles
                // ...
            }
        }
    }
    
    /**
     * Enqueue admin scripts
     * 
     * @return void
     */
    public function enqueueAdminScripts(): void {
        // Load admin scripts if defined
        $admin_scripts = $this->app->config('assets.admin_scripts', []);
        
        foreach ($admin_scripts as $handle => $script) {
            if (is_string($script)) {
                wp_enqueue_script(
                    "fasty-child-admin-{$handle}", 
                    FASTY_CHILD_URI . $script, 
                    ['jquery'], 
                    $this->getFileVersion(FASTY_CHILD_PATH . $script), 
                    true
                );
            } elseif (is_array($script)) {
                // Parameters similar to main scripts
                // ...
            }
        }
    }
    
    /**
     * Get file version based on file modification time
     * 
     * @param string $file Path to file
     * @return string|false Version string or false if file doesn't exist
     */
    private function getFileVersion($file) {
        return file_exists($file) ? filemtime($file) : false;
    }
} 