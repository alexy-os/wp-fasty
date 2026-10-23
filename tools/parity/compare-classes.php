<?php

/**
 * Parity of the UI8Kit 3 pilot without class canonicalization: themes/latte renders UI8Kit 3 primitives (classes
 * merged once by \UI8Kit\Latte\CnMerge), its Twig twin still the legacy line (classes joined verbatim).
 *
 *   $cmp = require 'compare-classes.php'; $cmp($latteHtml, $twigHtml) → null when equal, else the first difference
 *
 * Strict on everything but class lists: the DOM contract of normalize-dom.php (tags, attributes, hrefs, text) with
 * class attributes taken out must be identical. Class lists are compared element by element as token sets: the
 * Latte side may only drop duplicate tokens and the KNOWN conflict tokens below (the legacy line printed a preset
 * class next to the caller's override); any added token or any other missing token fails.
 * Drop this file once the Twig side is on UI8Kit 3 (then normalize.php strict again).
 *
 * @package Latte
 */

declare(strict_types=1);

return static function (string $latte, string $twig): ?string {
    /** Legacy preset tokens overridden by the caller's class (featured-grid link: block, 404 CTA: lg, alerts: py-2). */
    $known = ['inline-flex', 'text-sm', 'py-3'];
    /** Tokens the legacy merge (builder Twig runtime) wrongly drops next to ring-offset-*; UI8Kit 3.2 keeps them. */
    $fixed = ['ring-ring', 'focus:ring-ring', 'focus-visible:ring-ring'];
    static $dom = null;
    $dom ??= require __DIR__ . '/normalize-dom.php';
    $strip = static fn (string $h): string => $dom((string) preg_replace('/\sclass="[^"]*"/', '', $h));
    $a = $strip($latte);
    $b = $strip($twig);
    if ($a !== $b) {
        $i = 0;
        while ($i < min(strlen($a), strlen($b)) && $a[$i] === $b[$i]) {
            $i++;
        }
        return "DOM differs at byte {$i}\n  latte: …" . substr($a, max(0, $i - 80), 200)
            . "\n  twig:  …" . substr($b, max(0, $i - 80), 200);
    }
    $classes = static function (string $h): array {
        $doc = new \DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div>' . $h . '</div>', LIBXML_HTML_NODEFDTD | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        $out = [];
        foreach ($doc->getElementsByTagName('*') as $el) {
            $tokens = preg_split('/\s+/', trim($el->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY);
            $out[] = [$el->nodeName, $tokens ?: []];
        }
        return $out;
    };
    $ca = $classes($latte);
    $cb = $classes($twig);
    foreach ($ca as $k => [$tag, $tokens]) {
        $added = array_diff($tokens, $cb[$k][1], $fixed);
        $dropped = array_diff(array_unique($cb[$k][1]), $tokens);
        if ($added !== [] || array_diff($dropped, $known) !== []) {
            return "class list of <{$tag}> #{$k}: latte +[" . implode(' ', $added) . ']'
                . ' -[' . implode(' ', $dropped) . ']';
        }
    }
    return null;
};
