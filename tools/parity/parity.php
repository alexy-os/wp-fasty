<?php

/**
 * Latte ↔ Twig parity gate: every fixture context is rendered by LatteRenderer and TwigRenderer over the same
 * view root chain (child views → parent views → engine primitives); the normalized HTML must be identical.
 *
 *   php tools/parity/parity.php [suite…]     (bun run parity; no suite = all)
 *
 * Fixtures: tests/fixtures/parity/<suite>/*.json = { view, context, page? }. `page: true` renders like
 * ThemeService::renderPage (view → content → layout/default). Contexts are frozen ContextFactory snapshots
 * (tools/parity/dump-context.php) or hand-written edge cases. WordPress functions come from tests/bootstrap.php stubs.
 *
 * Suite `builder`: every landing of tools/builder/landings/*.json is normalized and compiled by wpfasty-builder to
 * Latte and to Twig (Compiler), bound paths filled with the catalog sample records, and rendered as a full page of
 * its archetype (child roots + the compiled view root) with both renderers.
 *
 * @package Latte
 */

declare(strict_types=1);

use WPFasty\Template\LatteRenderer;
use WPFasty\Template\RendererInterface;
use WPFasty\Template\TwigRenderer;
use WPFasty\Theme\PrimitivesLocator;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Compiler;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Source\LocalSource;

$root = dirname(__DIR__, 2);
require $root . '/tests/bootstrap.php';
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = $root . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

/** Suite → child-first view roots (relative to the repo). */
$suites = [
    'latte' => ['themes/latte/views'],
    'press' => ['themes/latte-press/views', 'themes/latte/views'],
    'folio' => ['themes/latte-folio/views', 'themes/latte/views'],
    'shop' => ['themes/latte-shop/views', 'themes/latte/views'],
    'builder' => [],
];
$out = static function (string $line): void {
    fwrite(STDOUT, $line . "\n");
};

$twigPrims = PrimitivesLocator::resolveTwig($root . '/themes/latte');
if ($twigPrims === null || !class_exists(\Twig\Environment::class)) {
    fwrite(STDERR, "✗ Twig primitives or twig/twig missing (bun run codegen:verify; composer install)\n");
    exit(1);
}

$normalize = require __DIR__ . '/normalize.php';

$render = static function (RendererInterface $r, array $fx): string {
    if (empty($fx['page'])) {
        return $r->render($fx['view'], $fx['context']);
    }
    $ctx = $fx['context'];
    $ctx['content'] = $r->render($fx['view'], $ctx);
    return $r->render('layout/default', $ctx);
};

$wanted = array_slice($argv, 1) ?: array_keys($suites);
$fail = 0;
$cache = sys_get_temp_dir() . '/wpfasty-parity-' . getmypid();
/** Builder landings: compiled Latte + Twig page of each landing, rendered in its archetype. */
$builder = static function () use ($root, $suites, $twigPrims, $cache, $render, $normalize, $out): int {
    $fail = 0;
    $src = $root . '/plugins/wpfasty-builder/sources/ui8kit';
    $catalog = new Catalog((new LocalSource('ui8kit', $src))->catalog());
    $blocks = new \WPFastyBuilder\BlockRenderer(['ui8kit' => new LocalSource('ui8kit', $src, '', false)], null);
    $fn = static fn (string $s, string $t, array $p): string => $blocks->render($s, $t, $p);
    $contexts = ['press' => 'latte/page', 'folio' => 'folio/front', 'shop' => 'shop/front'];
    $files = glob($root . '/tools/builder/landings/*.json') ?: [];
    sort($files);
    if (!$files) {
        $out('✗ builder: no landings');
        return 1;
    }
    foreach ($files as $i => $file) {
        $name = 'builder/' . basename($file, '.json');
        $arch = explode('-', basename($file, '.json'))[0];
        $spec = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        [$entry] = Layout::normalize($spec['layout'], 'ui8kit', $catalog, ['posts', 'projects', 'products', 'pages',
            'topics', 'project_types', 'product_cats'], ['id' => 'layout-' . $i, 'slug' => 'p', 'title' => 'P',
            'page_id' => $i + 1, 'version' => 1, 'created_at' => '2026-10-21T00:00:00Z',
            'updated_at' => '2026-10-21T00:00:00Z']);
        $dir = "{$cache}/builder-views/{$i}";
        @mkdir($dir . '/builder', 0775, true);
        foreach (Compiler::ENGINES as $engine => $ext) {
            file_put_contents("{$dir}/builder/page{$ext}", Compiler::compile($entry, $catalog, $engine));
        }
        // Bound paths get the catalog sample records of that path (what the editor previews).
        $wpfb = [];
        foreach (Layout::blocks($entry) as $b) {
            $variant = $catalog->variant((string) $b['block'], (string) $b['variant']);
            $props = Layout::merge((array) ($variant['props'] ?? []), $b['props']);
            foreach (array_keys($b['bindings']) as $path) {
                $v = $props;
                foreach (explode('.', (string) $path) as $k) {
                    $v = is_array($v) ? ($v[$k] ?? []) : [];
                }
                $wpfb[(string) $b['id']][(string) $path] = $v;
            }
        }
        $ctxFile = $root . '/tests/fixtures/parity/' . ($contexts[$arch] ?? 'latte/page') . '.json';
        $ctx = json_decode((string) file_get_contents($ctxFile), true, 512, JSON_THROW_ON_ERROR)['context'];
        $ctx['wpfb'] = $wpfb;
        $ctx['widgets']['sidebar'] = '';
        $own = $suites[$arch] ?? $suites['latte'];
        $roots = [$dir, ...array_map(static fn (string $d): string => $root . '/' . $d, $own)];
        $latte = new LatteRenderer([...$roots, $root . '/.workspaces/ui8kit-latte/ui'], "{$cache}/builder/{$i}");
        $latte->latte()->addFunction('wpfb_block', $fn);
        $twig = new TwigRenderer([...$roots, $twigPrims], "{$cache}/builder/{$i}/twig");
        $twig->twig()->registerUndefinedFunctionCallback(static fn (string $n): \Twig\TwigFunction|false
            => $n === 'wpfb_block' ? new \Twig\TwigFunction($n, $fn, ['is_safe' => ['html']]) : false);
        $fx = ['view' => 'builder/page', 'context' => $ctx, 'page' => true];
        try {
            $a = $normalize($render($latte, $fx));
            $b = $normalize($render($twig, $fx));
        } catch (\Throwable $e) {
            $out("✗ {$name}: " . get_class($e) . ': ' . $e->getMessage());
            $fail++;
            continue;
        }
        $n = substr_count($a, 'wpfb-block"');
        if ($a === $b && $n > 0 && !str_contains($a, '<!-- wpfasty-builder')) {
            $out("✓ {$name}: compiled landing ({$n} blocks) + layout/default identical (" . strlen($a) . ' B)');
            continue;
        }
        $fail++;
        $j = 0;
        while ($j < min(strlen($a), strlen($b)) && $a[$j] === $b[$j]) {
            $j++;
        }
        $out("✗ {$name}: {$n} blocks, differs at byte {$j}");
        $out('  latte: …' . substr($a, max(0, $j - 80), 200));
        $out('  twig:  …' . substr($b, max(0, $j - 80), 200));
    }
    return $fail;
};

foreach ($wanted as $suite) {
    if (!isset($suites[$suite])) {
        $out("✗ unknown suite {$suite} (have: " . implode(', ', array_keys($suites)) . ')');
        $fail++;
        continue;
    }
    if ($suite === 'builder') {
        $fail += $builder();
        continue;
    }
    $files = glob($root . "/tests/fixtures/parity/{$suite}/*.json") ?: [];
    sort($files);
    $views = array_map(static fn (string $d): string => $root . '/' . $d, $suites[$suite]);
    // Coverage: the suite's own (first) view root has a Twig twin for every Latte view.
    $own = $views[0];
    $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($own, \FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $path = (string) $f;
        if (str_ends_with($path, '.latte') && !is_file(substr($path, 0, -6) . '.html.twig')) {
            $out('✗ ' . $suite . ': no Twig twin for ' . substr($path, strlen($root) + 1));
            $fail++;
        }
    }
    if (!$files) {
        $out("✗ {$suite}: no fixtures");
        $fail++;
        continue;
    }
    $latte = new LatteRenderer([...$views, $root . '/.workspaces/ui8kit-latte/ui'], "{$cache}/{$suite}");
    $twig = new TwigRenderer([...$views, $twigPrims], "{$cache}/{$suite}/twig");
    foreach ($files as $file) {
        $fx = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        $name = $suite . '/' . basename($file, '.json');
        $kind = empty($fx['page']) ? $fx['view'] : "{$fx['view']} + layout/default";
        if (!$twig->exists($fx['view'])) {
            $out("✗ {$name}: no Twig view {$fx['view']}");
            $fail++;
            continue;
        }
        try {
            $a = $normalize($render($latte, $fx));
            $b = $normalize($render($twig, $fx));
        } catch (\Throwable $e) {
            $out("✗ {$name}: " . get_class($e) . ': ' . $e->getMessage());
            $fail++;
            continue;
        }
        if ($a === $b) {
            $out("✓ {$name}: {$kind} identical (" . strlen($a) . ' B)');
            continue;
        }
        $fail++;
        $i = 0;
        $max = min(strlen($a), strlen($b));
        while ($i < $max && $a[$i] === $b[$i]) {
            $i++;
        }
        $out("✗ {$name}: differs at byte {$i}");
        $out('  latte: …' . substr($a, max(0, $i - 80), 200));
        $out('  twig:  …' . substr($b, max(0, $i - 80), 200));
    }
}
$out($fail ? "{$fail} failed" : 'Latte ↔ Twig parity OK');
exit($fail ? 1 : 0);
