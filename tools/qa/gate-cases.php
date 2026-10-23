<?php

/**
 * Cases for `ui-gates html` (JSON [{id, html}]):
 *   php tools/qa/gate-cases.php blocks <out.json>          every builder block × variant, both engines (BlockRenderer)
 *   php tools/qa/gate-cases.php theme <dump-dir> <out.json> theme renders kept by `PARITY_DUMP=<dir> bun run parity`
 *
 * @package Latte
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
[$mode, $a, $b] = [$argv[1] ?? '', $argv[2] ?? '', $argv[3] ?? ''];
$cases = [];
if ($mode === 'blocks') {
    require $root . '/tests/bootstrap.php';
    spl_autoload_register(static function (string $c) use ($root): void {
        if (str_starts_with($c, 'WPFastyBuilder\\')) {
            $f = $root . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($c, 15)) . '.php';
            if (is_file($f)) {
                require $f;
            }
        }
    });
    $src = $root . '/plugins/wpfasty-builder/sources/ui8kit';
    $r = new \WPFastyBuilder\BlockRenderer([
        'latte' => new \WPFastyBuilder\Source\LocalSource('latte', "$src/latte", 'latte'),
        'twig' => new \WPFastyBuilder\Source\LocalSource('twig', "$src/twig", 'twig'),
    ], null);
    foreach (['latte', 'twig'] as $e) {
        $cat = json_decode((string) file_get_contents("$src/$e/catalog.json"), true, 512, JSON_THROW_ON_ERROR);
        foreach ($cat['blocks'] as $blk) {
            foreach ($blk['variants'] as $v) {
                $html = $r->render($e, (string) $v['template'], (array) $v['props']);
                if ($html === '' || str_contains($html, '<!-- wpfasty-builder')) {
                    fwrite(STDERR, "✗ $e {$blk['id']}/{$v['id']} did not render\n");
                    exit(1);
                }
                $cases[] = ['id' => "$e:{$blk['id']}/{$v['id']}", 'html' => $html];
            }
        }
    }
    $out = $a;
} elseif ($mode === 'theme') {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($a, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (str_ends_with((string) $f, '.html')) {
            $html = (string) file_get_contents((string) $f);
            // the parity renders start at <html> (no doctype)
            $cases[] = ['id' => substr((string) $f, strlen($a) + 1),
                'html' => preg_match('/^\s*<html/i', $html) ? "<!DOCTYPE html>\n" . $html : $html];
        }
    }
    usort($cases, static fn (array $x, array $y): int => strcmp($x['id'], $y['id']));
    $out = $b;
} else {
    fwrite(STDERR, "usage: gate-cases.php blocks <out.json> | theme <dump-dir> <out.json>\n");
    exit(2);
}
@mkdir(dirname($out), 0775, true);
file_put_contents($out, json_encode($cases, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
fwrite(STDOUT, count($cases) . " cases → $out\n");
