<?php

/**
 * Block fragments of a rendered builder page, for the render-block check (tools/builder/render-block-check.php,
 * tests/FragmentTest.php): `blocks($pageHtml)` → [block id => inner HTML of its `[data-wpfb-block]` wrapper],
 * `inner($fragmentHtml)` → the same serialization of a fragment from POST /render-block. Both go through libxml and are
 * whitespace-collapsed, so they compare equal exactly when the DOM and the text are the same.
 *
 * @package WPFastyBuilder
 */

declare(strict_types=1);

$load = static function (string $html, bool $page): \DOMDocument {
    $doc = new \DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="UTF-8">' . ($page ? $html : '<div id="wpfb-fragment-root">' . $html . '</div>'),
        ($page ? 0 : LIBXML_HTML_NOIMPLIED) | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    return $doc;
};
$inner = static function (\DOMElement $el): string {
    $out = '';
    foreach ($el->childNodes as $c) {
        $out .= (string) $el->ownerDocument?->saveHTML($c);
    }
    return trim((string) preg_replace('/\s+/u', ' ', $out));
};

return [
    'blocks' => static function (string $html) use ($load, $inner): array {
        $doc = $load($html, true);
        $out = [];
        foreach ((new \DOMXPath($doc))->query('//*[@data-wpfb-block]') ?: [] as $el) {
            if ($el instanceof \DOMElement) {
                $out[$el->getAttribute('data-wpfb-block')] = $inner($el);
            }
        }
        return $out;
    },
    'inner' => static function (string $fragment) use ($load, $inner): string {
        $root = $load($fragment, false)->getElementById('wpfb-fragment-root');
        return $root instanceof \DOMElement ? $inner($root) : '';
    },
];
