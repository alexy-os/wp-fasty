<?php
/**
 * bun run parity:press — renders every tests/fixtures/parity/press/*.json context with LatteRenderer and TwigRenderer
 * over the same root chain (press views → parent views → engine primitives) and asserts normalized HTML equality.
 * Needs themes/latte/vendor (Latte), root vendor (twig/twig, dev only) and Twig primitives (bun run codegen:verify).
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/tests/bootstrap.php';

use WPFasty\Template\LatteRenderer;
use WPFasty\Template\TwigRenderer;
use WPFasty\Theme\PrimitivesLocator;

$twigPrims = PrimitivesLocator::resolveTwig($root . '/themes/latte');
if ($twigPrims === null || !class_exists(\Twig\Environment::class)) {
    fwrite(STDERR, "✗ Twig primitives or twig/twig missing (run bun run codegen:verify and composer install)\n");
    exit(1);
}
$views = [$root . '/themes/latte-press/views', $root . '/themes/latte/views'];
$cache = sys_get_temp_dir() . '/wpfasty-parity-' . getmypid();
$latte = new LatteRenderer([...$views, $root . '/.workspaces/ui8kit-latte/ui'], $cache);
$twig = new TwigRenderer([...$views, $twigPrims], $cache . '/twig');

/** Whitespace between/inside tags collapsed; equivalent entity spellings unified. */
function normalize(string $html): string
{
    $html = str_replace(['&#039;', '&#39;', '&apos;', '&#34;'], ['&#x27;', '&#x27;', '&#x27;', '&quot;'], $html);
    // Text nodes: Latte leaves quotes literal, Twig encodes them; both are the same DOM text.
    $html = (string) preg_replace_callback('/>([^<]*)</', static fn (array $m): string
        => '>' . str_replace(['&quot;', '&#x27;'], ['"', "'"], $m[1]) . '<', $html);
    $html = (string) preg_replace('/\s+/', ' ', $html);
    $html = (string) preg_replace('/>\s+</', '><', $html);
    $html = (string) preg_replace('/\s+>/', '>', $html);
    return trim($html);
}

$fail = 0;
$files = glob($root . '/tests/fixtures/parity/press/*.json') ?: [];
sort($files);
foreach ($files as $file) {
    $fx = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    $name = basename($file, '.json');
    if (!$twig->exists($fx['view'])) {
        echo "✗ {$name}: no Twig view {$fx['view']}\n";
        $fail++;
        continue;
    }
    $a = normalize($latte->render($fx['view'], $fx['context']));
    $b = normalize($twig->render($fx['view'], $fx['context']));
    if ($a === $b) {
        echo "✓ {$name}: {$fx['view']} identical (" . strlen($a) . " B)\n";
        continue;
    }
    $fail++;
    $i = 0;
    while ($i < min(strlen($a), strlen($b)) && $a[$i] === $b[$i]) {
        $i++;
    }
    echo "✗ {$name}: differs at byte {$i}\n  latte: …" . substr($a, max(0, $i - 60), 160) . "\n  twig:  …" . substr($b, max(0, $i - 60), 160) . "\n";
}
if (!$files) {
    echo "✗ no fixtures\n";
    $fail++;
}
echo $fail ? "{$fail} failed\n" : "press Latte↔Twig parity OK\n";
exit($fail ? 1 : 0);
