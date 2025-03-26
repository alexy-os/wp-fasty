<?php
declare(strict_types=1);

namespace FastyChild\Core;

use FastyChild\Hooks\Constants;

/**
 * Utility class with helper methods
 */
final class Utils
{
    /**
     * Default allowed HTML tags for sanitization
     * @var array
     */
    public const ALLOWED_HTML_TAGS = [
        'a' => [
            'href' => [],
            'title' => [],
            'target' => [],
            'rel' => [],
            'class' => []
        ],
        'br' => [],
        'em' => [],
        'strong' => [],
        'p' => ['class' => []],
        'span' => ['class' => []],
        'div' => ['class' => []],
        'ul' => ['class' => []],
        'li' => ['class' => []],
        'h1' => ['class' => []],
        'h2' => ['class' => []],
        'h3' => ['class' => []],
        'h4' => ['class' => []],
        'h5' => ['class' => []],
        'h6' => ['class' => []],
    ];

    /**
     * Convert a path to a URL
     *
     * @param string $path Path to convert
     * @return string URL
     */
    public static function pathToUrl(string $path): string
    {
        // Check if path is absolute
        if (strpos($path, FASTY_CHILD_PATH) === 0) {
            return str_replace(FASTY_CHILD_PATH, FASTY_CHILD_URI, $path);
        }
        
        // Check if path is relative
        return FASTY_CHILD_URI . '/' . ltrim($path, '/');
    }
    
    /**
     * Get file version based on modification time
     *
     * @param string $file File path
     * @return string|null Version string or null if file not found
     */
    public static function getFileVersion(string $file): ?string
    {
        if (!file_exists($file)) {
            return null;
        }
        
        return (string)filemtime($file);
    }
    
    /**
     * Check if a file exists and return appropriate URI
     *
     * @param string $uri URI to check
     * @param string $default Default URI if file not found
     * @return string Original URI if file exists, default otherwise
     */
    public static function fileExistsUri(string $uri, string $default = ''): string
    {
        $path = str_replace(
            [get_stylesheet_directory_uri(), get_template_directory_uri()],
            [get_stylesheet_directory(), get_template_directory()],
            $uri
        );
        
        return file_exists($path) ? $uri : $default;
    }
    
    /**
     * Sanitize HTML content with allowed tags
     *
     * @param string $content Content to sanitize
     * @param array $allowedTags Allowed HTML tags
     * @return string Sanitized content
     */
    public static function sanitizeHtml(string $content, array $allowedTags = []): string
    {
        // Default allowed tags if none specified
        if (empty($allowedTags)) {
            $allowedTags = self::ALLOWED_HTML_TAGS;
        }
        
        return wp_kses($content, $allowedTags);
    }
    
    /**
     * Check if debug mode is enabled
     *
     * @return bool True if debug mode is enabled
     */
    public static function isDebugEnabled(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
    
    /**
     * Check if the current environment is development
     *
     * @return bool True if development environment
     */
    public static function isDevelopmentEnvironment(): bool
    {
        // Check if WP_ENVIRONMENT_TYPE is defined and set to 'development'
        if (defined('WP_ENVIRONMENT_TYPE') && WP_ENVIRONMENT_TYPE === 'development') {
            return true;
        }
        
        // Fallback to WP_DEBUG
        return self::isDebugEnabled();
    }
    
    /**
     * Get the current framework version
     *
     * @return string Framework version
     */
    public static function getFrameworkVersion(): string
    {
        return Constants::VERSION;
    }
    
    /**
     * Compare framework versions
     *
     * @param string $version1 First version
     * @param string $version2 Second version
     * @return int -1 if $version1 < $version2, 0 if equal, 1 if $version1 > $version2
     */
    public static function compareVersions(string $version1, string $version2): int
    {
        return version_compare($version1, $version2);
    }
    
    /**
     * Check if current framework version is at least the given version
     *
     * @param string $version Version to check against
     * @return bool True if current version is at least the given version
     */
    public static function isVersionAtLeast(string $version): bool
    {
        return self::compareVersions(self::getFrameworkVersion(), $version) >= 0;
    }
} 