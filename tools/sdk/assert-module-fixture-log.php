<?php

declare(strict_types=1);

// Upstream intentionally logs successful structured audit events. Accept ONLY those records;
// do not discard PHP warnings/notices/fatals or host/module errors from fixture processes.
$file = $argv[1] ?? '';
$lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : false;
if (false === $lines || [] === $lines) {
    throw new RuntimeException('Module fixture audit log missing.');
}
foreach ($lines as $line) {
    $json = preg_replace('/^\[[^\]]+\] /', '', $line);
    $entry = json_decode((string) $json, true);
    if (!is_array($entry) || !in_array($entry['moduly_audit'] ?? '', ['install', 'enable', 'disable', 'remove'], true) || 'ok' !== ($entry['result'] ?? '') || 'builder-agent' !== ($entry['module'] ?? '') || '0.1.2' !== ($entry['version'] ?? '') || array_diff(array_keys($entry), ['moduly_audit', 'module', 'version', 'result', 'user'])) {
        throw new RuntimeException('Unexpected module fixture log record; inspect the CI artifact/log, not a suppressed warning.');
    }
}
echo "PASS only expected successful ModulY fixture audit records; no warnings/notices/errors.\n";
