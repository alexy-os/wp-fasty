<?php
/**
 * PHPCS wrapper script
 * 
 * This script is used to run PHPCS without loading WordPress functions.
 */

// Disable autoloading of WordPress-specific files
define('PHPCS_DISABLE_WP_AUTOLOAD', true);

// Include the PHPCS autoloader only
require_once __DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php';

// Run PHPCS
$phpcs = new PHP_CodeSniffer\Runner();
$phpcs->runPHPCS();