<?php

/**
 * DOM normalizer of the block parity, the PHP twin of codegen-x's parity contract (xgen/check templ/normalize.ts):
 * the node tree with attributes sorted by name, class tokens sorted, empty class dropped, text nodes
 * whitespace-collapsed and trimmed. The generated Latte and Twig block packages differ only in template whitespace
 * around text (indentation), which this contract treats as the same tree.
 *
 * @package Latte
 */

declare(strict_types=1);

return static function (string $html): string {
    $doc = new \DOMDocument();
    $prev = libxml_use_internal_errors(true);
    // A whole page (doctype / <html>) is parsed as a document, a fragment inside a wrapper element.
    $page = preg_match('/^\s*(<!doctype|<html)/i', $html) === 1;
    $doc->loadHTML(
        $page
            ? '<?xml encoding="UTF-8">' . preg_replace('/^\s*<!doctype[^>]*>/i', '', $html)
            : '<?xml encoding="UTF-8"><div id="wpfasty-parity-root">' . $html . '</div>',
        ($page ? 0 : LIBXML_HTML_NOIMPLIED) | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $walk = static function (\DOMNode $n) use (&$walk): string {
        if ($n instanceof \DOMText) {
            return trim((string) preg_replace('/\s+/u', ' ', $n->data));
        }
        if ($n instanceof \DOMComment || !$n instanceof \DOMElement) {
            return '';
        }
        $attrs = [];
        foreach ($n->attributes ?? [] as $a) {
            $v = (string) $a->value;
            if ($a->name === 'class') {
                $tokens = preg_split('/\s+/', trim($v)) ?: [];
                $tokens = array_values(array_filter($tokens, static fn (string $t): bool => $t !== ''));
                sort($tokens);
                if ($tokens === []) {
                    continue;
                }
                $v = implode(' ', $tokens);
            }
            $attrs[$a->name] = ' ' . $a->name . '="' . htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }
        ksort($attrs);
        $kids = '';
        foreach ($n->childNodes as $c) {
            $kids .= $walk($c);
        }
        return '<' . $n->tagName . implode('', $attrs) . '>' . $kids . '</' . $n->tagName . '>';
    };
    if ($page) {
        return $doc->documentElement !== null ? $walk($doc->documentElement) : '';
    }
    $root = $doc->getElementById('wpfasty-parity-root');
    $out = '';
    foreach ($root?->childNodes ?? [] as $c) {
        $out .= $walk($c);
    }
    return $out;
};
