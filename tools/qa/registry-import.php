<?php
/**
 * QA/CI only: imports a remote builder registry into the active site, exactly as the admin "Import" button does
 * (WPFastyBuilder\Sources::import — runtime must equal the site engine, sha256 must match, templates must exist).
 *   wp eval-file tools/qa/registry-import.php <id> <url>
 * Prints `Success: …` and exits 0, or `Error: …` and exits 1 (CI asserts both outcomes).
 */

if (!class_exists(WPFastyBuilder\Sources::class)) {
    WP_CLI::error('Activate the wpfasty-builder plugin first.');
}
[$id, $url] = [$args[0] ?? '', $args[1] ?? ''];
try {
    $r = WPFastyBuilder\Sources::import($id, $url);
} catch (Throwable $e) {
    WP_CLI::error($e->getMessage());
}
WP_CLI::success(sprintf('%s: %s registry v%s, %d blocks', $id, $r['runtime'], $r['version'], $r['blocks']));
