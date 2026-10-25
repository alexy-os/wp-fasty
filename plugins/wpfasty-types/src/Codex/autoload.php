<?php

declare(strict_types=1);

// Codex classes of the plugin (no Composer in the plugin zip).
spl_autoload_register(static function (string $class): void {
    $prefix = 'WPFastyTypes\\Codex\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__ . '/' . substr($class, strlen($prefix)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
