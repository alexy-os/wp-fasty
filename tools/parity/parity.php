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
 * @package Latte
 */

declare(strict_types=1);

use WPFasty\Template\LatteRenderer;
use WPFasty\Template\RendererInterface;
use WPFasty\Template\TwigRenderer;
use WPFasty\Theme\PrimitivesLocator;

$root = dirname(__DIR__, 2);
require $root . '/tests/bootstrap.php';

/** Suite → child-first view roots (relative to the repo). */
$suites = [
    'latte' => ['themes/latte/views'],
    'press' => ['themes/latte-press/views', 'themes/latte/views'],
    'folio' => ['themes/latte-folio/views', 'themes/latte/views'],
    'shop' => ['themes/latte-shop/views', 'themes/latte/views'],
];
$out = static function (string $line): void {
    fwrite(STDOUT, $line . "\n");
};

$twigPrims = PrimitivesLocator::resolveTwig($root . '/themes/latte');
if ($twigPrims === null || !class_exists(\Twig\Environment::class)) {
    fwrite(STDERR, "✗ Twig primitives or twig/twig missing (bun run codegen:verify; composer install)\n");
    exit(1);
}

/** Whitespace collapsed, equivalent entity spellings unified (Latte keeps quotes literal in text, Twig encodes). */
$normalize = static function (string $html): string {
    $html = str_replace(['&#039;', '&#39;', '&apos;', '&#34;'], ['&#x27;', '&#x27;', '&#x27;', '&quot;'], $html);
    $html = (string) preg_replace_callback('/>([^<]*)</', static fn (array $m): string
        => '>' . str_replace(['&quot;', '&#x27;'], ['"', "'"], $m[1]) . '<', $html);
    $html = (string) preg_replace('/\s+/', ' ', $html);
    $html = (string) preg_replace('/>\s+</', '><', $html);
    $html = (string) preg_replace('/\s+>/', '>', $html);
    return trim($html);
};

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
foreach ($wanted as $suite) {
    if (!isset($suites[$suite])) {
        $out("✗ unknown suite {$suite} (have: " . implode(', ', array_keys($suites)) . ')');
        $fail++;
        continue;
    }
    $files = glob($root . "/tests/fixtures/parity/{$suite}/*.json") ?: [];
    sort($files);
    if (!$files) {
        $out("- {$suite}: no fixtures (skipped)");
        continue;
    }
    $views = array_map(static fn (string $d): string => $root . '/' . $d, $suites[$suite]);
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
