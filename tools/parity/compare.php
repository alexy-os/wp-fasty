<?php

/**
 * Live parity: compare two rendered HTML files (Latte vs Twig response of the same URL) after normalization.
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
$i = 0;
$max = min(strlen($a), strlen($b));
while ($i < $max && $a[$i] === $b[$i]) {
    $i++;
}
fwrite(STDOUT, "differs at byte {$i}\n");
fwrite(STDOUT, '  a: …' . substr($a, max(0, $i - 80), 200) . "\n" . '  b: …' . substr($b, max(0, $i - 80), 200) . "\n");
exit(1);
