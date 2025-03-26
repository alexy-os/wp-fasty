<?php
declare(strict_types=1);

namespace FastyChild\Core;

/**
 * Core constants for the FastyChild framework
 */
class Constants
{
    /**
     * Framework version
     */
    public const VERSION = '1.0.0';

    /**
     * Log prefix for framework messages
     */
    public const LOG_PREFIX = 'fasty_';

    /**
     * Configuration cache prefix
     */
    public const CONFIG_CACHE_PREFIX = 'fasty_config_';

    /**
     * Default configuration cache time (24 hours)
     */
    public const CONFIG_CACHE_TIME = DAY_IN_SECONDS;

    /**
     * Initialize framework constants
     * This method should be called before any other framework code
     */
    public static function init(): void
    {
        // Define framework version
        if (!defined('FASTY_VERSION')) {
            define('FASTY_VERSION', self::VERSION);
        }

        // Define log prefix
        if (!defined('FASTY_LOG_PREFIX')) {
            define('FASTY_LOG_PREFIX', self::LOG_PREFIX);
        }

        // Define configuration cache prefix
        if (!defined('FASTY_CONFIG_CACHE_PREFIX')) {
            define('FASTY_CONFIG_CACHE_PREFIX', self::CONFIG_CACHE_PREFIX);
        }

        // Define configuration cache time
        if (!defined('FASTY_CONFIG_CACHE_TIME')) {
            define('FASTY_CONFIG_CACHE_TIME', self::CONFIG_CACHE_TIME);
        }

        // Define theme paths and URIs if not already defined
        if (!defined('FASTY_CHILD_PATH')) {
            define('FASTY_CHILD_PATH', get_stylesheet_directory());
        }
        if (!defined('FASTY_CHILD_URI')) {
            define('FASTY_CHILD_URI', get_stylesheet_directory_uri());
        }
        if (!defined('FASTY_PARENT_PATH')) {
            define('FASTY_PARENT_PATH', get_template_directory());
        }
        if (!defined('FASTY_PARENT_URI')) {
            define('FASTY_PARENT_URI', get_template_directory_uri());
        }
    }
} 