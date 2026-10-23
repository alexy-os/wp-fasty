<?php

/**
 * Live parity: compare two rendered HTML files (Latte vs Twig response of the same URL) after normalization; when
 * the strict normalization differs, the DOM contract decides (builder landings).
 *   php tools/parity/compare.php latte.html twig.html   → exit 1 and the first difference when they differ
 *
 * @package Latte
 */

declare(strict_types=1);

$normalize = require __DIR__ . '/normalize.php';
[$fileA, $fileB] = array_slice($argv, 1) + ['', ''];
if (!is_file($fileA) || !is_file($fileB)) {
    fwrite(STDERR, "usage: php tools/parity/compare.php <a.html> <b.html>\n");
    exit(2);
}
$a = $normalize((string) file_get_contents($fileA));
$b = $normalize((string) file_get_contents($fileB));
if ($a === $b) {
    fwrite(STDOUT, 'identical (' . strlen($a) . " B)\n");
    exit(0);
}
// Builder blocks come from the generated Latte / Twig packages, which differ only in template whitespace around text:
// fall back to the DOM contract of the block parity (tools/parity/normalize-dom.php).
$dom = require __DIR__ . '/normalize-dom.php';
$da = $dom((string) file_get_contents($fileA));
$db = $dom((string) file_get_contents($fileB));
if ($da === $db) {
    fwrite(STDOUT, 'DOM-identical (' . strlen($da) . " B; text whitespace differs)\n");
    exit(0);
}
// UI8Kit 3 pilot: the Latte parent renders UI8Kit 3 primitives + kit presets, the Twig parent the legacy line —
// same tree once class lists are canonicalized with the UI8Kit 3 merge (tools/parity/normalize-kit.php).
$kit = require __DIR__ . '/normalize-kit.php';
$ka = $kit((string) file_get_contents($fileA));
if ($ka === $kit((string) file_get_contents($fileB))) {
    fwrite(STDOUT, 'DOM-identical after the UI8Kit 3 class merge (' . strlen($ka) . " B)\n");
    exit(0);
}
$i = 0;
$max = min(strlen($a), strlen($b));
while ($i < $max && $a[$i] === $b[$i]) {
    $i++;
}
fwrite(STDOUT, "differs at byte {$i}\n");
fwrite(STDOUT, '  a: …' . substr($a, max(0, $i - 80), 200) . "\n" . '  b: …' . substr($b, max(0, $i - 80), 200) . "\n");
exit(1);
