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
 * its archetype (child roots + the compiled view root) with both renderers — Latte page + Latte blocks vs Twig page
 * + Twig blocks; compared as DOM trees (normalize-dom.php).
 *
 * Suite `blocks`: every block and every variant of the builder source catalog
 * (plugins/wpfasty-builder/sources/ui8kit/catalog.json — its sample props are the fixtures) rendered from the
 * generated Latte package and the generated Twig package; compared as DOM trees (tools/parity/normalize-dom.php,
 * the codegen-x parity contract: text trimmed, attributes and class tokens sorted).
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
    'blocks' => [],
];
$out = static function (string $line): void {
    fwrite(STDOUT, $line . "\n");
};

$twigPrims = PrimitivesLocator::resolveTwig($root . '/themes/latte');
if ($twigPrims === null || !class_exists(\Twig\Environment::class)) {
    fwrite(STDERR, "✗ Twig primitives or twig/twig missing (bun run codegen:verify; composer install)\n");
    exit(1);
}

// UI8Kit 3 pilot: Latte views render UI8Kit 3 primitives + kit presets, Twig the legacy line (see normalize-kit.php).
$normalize = require __DIR__ . '/normalize-kit.php';
$lattePrims = PrimitivesLocator::latteRoots($root . '/themes/latte');

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
$builder = static function () use ($root, $suites, $twigPrims, $lattePrims, $cache, $render, $out): int {
    $fail = 0;
    // Blocks render from the generated Latte / Twig packages: compared as DOM trees like suite blocks (class lists
    // canonicalized with the UI8Kit 3 merge: the layout around them is on UI8Kit 3, see normalize-kit.php).
    $normalize = require __DIR__ . '/normalize-kit.php';
    $src = $root . '/plugins/wpfasty-builder/sources/ui8kit';
    // One catalog and one block renderer per runtime (isolated runtimes; the Twig page never sees a Latte source).
    $catalogs = [];
    $fns = [];
    foreach (['latte', 'twig'] as $rt) {
        $one = new LocalSource('ui8kit', "{$src}/{$rt}", $rt);
        $catalogs[$rt] = new Catalog($one->catalog());
        $r = new \WPFastyBuilder\BlockRenderer(['ui8kit' => $one], null);
        $fns[$rt] = static fn (string $s, string $t, array $p): string => $r->render($s, $t, $p);
    }
    $catalog = $catalogs['latte'];
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
            file_put_contents("{$dir}/builder/page{$ext}", Compiler::compile($entry, $catalogs[$engine], $engine));
        }
        $calls = array_map(
            static fn (string $ext): int
                => substr_count((string) file_get_contents("{$dir}/builder/page{$ext}"), 'wpfb_block('),
            Compiler::ENGINES,
        );
        if ($calls['latte'] !== $calls['twig'] || $calls['latte'] === 0) {
            $out("✗ {$name}: compiled blocks latte {$calls['latte']} ≠ twig {$calls['twig']}");
            $fail++;
            continue;
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
        $latte = new LatteRenderer([...$roots, ...$lattePrims], "{$cache}/builder/{$i}");
        $latte->latte()->addFunction('wpfb_block', $fns['latte']);
        $twig = new TwigRenderer([...$roots, $twigPrims], "{$cache}/builder/{$i}/twig");
        $twig->twig()->registerUndefinedFunctionCallback(static fn (string $n): \Twig\TwigFunction|false
            => $n === 'wpfb_block' ? new \Twig\TwigFunction($n, $fns['twig'], ['is_safe' => ['html']]) : false);
        $fx = ['view' => 'builder/page', 'context' => $ctx, 'page' => true];
        try {
            $rawA = $render($latte, $fx);
            $rawB = $render($twig, $fx);
            // a block that failed to render leaves an HTML comment (dropped by the DOM normalizer): fail on it
            $at = strpos($rawA . $rawB, '<!-- wpfasty-builder');
            if ($at !== false) {
                throw new \RuntimeException('a block did not render: ' . substr($rawA . $rawB, $at, 160));
            }
            $a = $normalize($rawA);
            $b = $normalize($rawB);
        } catch (\Throwable $e) {
            $out("✗ {$name}: " . get_class($e) . ': ' . $e->getMessage());
            $fail++;
            continue;
        }
        $n = substr_count($a, 'wpfb-block"');
        if ($a === $b && $n > 0) {
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

/** Every block × variant of the builder source: generated Latte vs generated Twig package, DOM-equal. */
$blocksSuite = static function () use ($root, $out): int {
    $src = $root . '/plugins/wpfasty-builder/sources/ui8kit';
    if (!is_dir($src . '/twig/blocks')) {
        $out('✗ blocks: no Twig package in ' . substr($src, strlen($root) + 1) . '/twig');
        return 1;
    }
    foreach (['CnMerge', 'Runtime', 'Extension'] as $f) {
        require_once "{$src}/twig/php/{$f}.php";
    }
    $dom = require __DIR__ . '/normalize-dom.php';
    $catalog = json_decode((string) file_get_contents($src . '/latte/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    $twigCatalog = json_decode((string) file_get_contents($src . '/twig/catalog.json'), true, 512, JSON_THROW_ON_ERROR);
    $twigTemplates = [];
    foreach ((array) $twigCatalog['blocks'] as $b) {
        foreach ((array) $b['variants'] as $v) {
            $twigTemplates["{$b['id']}/{$v['id']}"] = (string) $v['template'];
        }
    }
    $latte = new \WPFastyBuilder\BlockRenderer(['ui8kit' => new LocalSource('ui8kit', $src . '/latte', 'latte')], null);
    $twig = \UI8Kit\Twig\Extension::environment($src . '/twig');
    $fail = 0;
    $n = 0;
    $blocks = 0;
    foreach ((array) $catalog['blocks'] as $b) {
        $blocks++;
        foreach ((array) $b['variants'] as $v) {
            $n++;
            $name = "blocks/{$b['id']}/{$v['id']}";
            $tpl = (string) $v['template'];
            try {
                $a = $dom($latte->render('ui8kit', $tpl, (array) $v['props']));
                // the twig catalog's own template of the same block/variant (one catalog per runtime)
                $twigTpl = $twigTemplates["{$b['id']}/{$v['id']}"]
                    ?? throw new \RuntimeException('not in the twig catalog');
                $t = $dom($twig->render($twigTpl, ['p' => (array) $v['props']]));
            } catch (\Throwable $e) {
                $out("✗ {$name}: " . get_class($e) . ': ' . $e->getMessage());
                $fail++;
                continue;
            }
            if ($a !== '' && $a === $t && !str_contains($a, 'wpfasty-builder:')) {
                continue;
            }
            $fail++;
            $j = 0;
            while ($j < min(strlen($a), strlen($t)) && $a[$j] === $t[$j]) {
                $j++;
            }
            $out("✗ {$name} ({$tpl}): differs at byte {$j}");
            $out('  latte: …' . substr($a, max(0, $j - 80), 200));
            $out('  twig:  …' . substr($t, max(0, $j - 80), 200));
        }
    }
    // Coverage: every generated Latte template (blocks and ui parts) has its Twig twin.
    $dir = new \RecursiveDirectoryIterator($src . '/latte', \FilesystemIterator::SKIP_DOTS);
    $it = new \RecursiveIteratorIterator($dir);
    $templates = 0;
    foreach ($it as $f) {
        $rel = substr((string) $f, strlen($src . '/latte/'));
        if (!str_ends_with($rel, '.latte') || str_starts_with($rel, 'php/')) {
            continue;
        }
        $templates++;
        if (!is_file($src . '/twig/' . substr($rel, 0, -6) . '.html.twig')) {
            $out("✗ blocks: no Twig twin for {$rel}");
            $fail++;
        }
    }
    $out($fail
        ? "✗ blocks: {$fail} problem(s) in {$n} variants / {$templates} templates"
        : "✓ blocks: {$blocks} blocks, {$n} variants DOM-identical; {$templates} Latte templates all have a Twig twin");
    return $fail;
};

foreach ($wanted as $suite) {
    if ($suite === 'blocks') {
        $fail += $blocksSuite();
        continue;
    }
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
    $latte = new LatteRenderer([...$views, ...$lattePrims], "{$cache}/{$suite}");
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
