<?php

/**
 * mu-plugin used by tools/qa/snapshots.sh: writes every template context (after all `wpfasty_context` filters) to
 * WPFASTY_CONTEXT_DUMP/<n>.json as {"kind", "url", "context"} for tools/qa/context-schema.php. No-op unless that
 * directory exists. Never ship it.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_filter('wpfasty_context', static function ($context) {
    $dir = sys_get_temp_dir().'/wpfasty-contexts';
    if (!is_array($context) || !is_dir($dir)) {
        return $context;
    }
    $kind = isset($context['error']) ? 'error' : (isset($context['archive']) ? 'archive' : 'page');
    $url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
    $file = $dir.'/'.get_stylesheet().'-'.md5($url).'.json';
    file_put_contents($file, (string) wp_json_encode(['kind' => $kind, 'url' => $url, 'context' => $context])); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- QA dump

    return $context;
}, PHP_INT_MAX);
