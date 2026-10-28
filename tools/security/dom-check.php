<?php

/**
 * DOM check of rendered HTML for XSS sinks (tools/security/xss-fuzz.php, tests/SecurityXssTest.php). Parses the HTML
 * like a browser would see it (libxml HTML parser; attribute values entity-decoded) and returns the findings:
 *   script            a <script> element (more than the baseline render has)
 *   on-attr:<name>    an on* event-handler attribute
 *   url:<attr>        a URL attribute (href, src, srcset, action, formaction, poster, cite, background, xlink:href,
 *                     data, ping) whose URL — ASCII whitespace / control characters removed, case folded — has a
 *                     javascript:, vbscript:, livescript: or data: scheme; data:image/(png|jpeg|gif|webp|avif|svg+xml)
 *                     is allowed only in <img>/<source> src and srcset
 *   style-url         url(javascript:/vbscript:/data:…) or expression( in a style attribute
 *   attr-name         an attribute name carrying the payload (dynamic attribute names)
 *   element:<tag>     iframe, frame, object, embed, base, meta, link, style, math elements beyond the baseline
 *   ssti              1337 (= 7*191 of the probes) in the output where neither the payload nor the baseline has it
 *
 * @package WPFastyBuilder
 */

declare(strict_types=1);

return static function (string $html, string $payload = '', string $baseline = ''): array {
    $parse = static function (string $h): DOMXPath {
        $doc = new DOMDocument();
        $prev = libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="UTF-8"><div id="wpfb-check-root">' . $h . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return new DOMXPath($doc);
    };
    $count = static fn (DOMXPath $xp, string $tag): int => (int) $xp->evaluate("count(//$tag)");
    $xp = $parse($html);
    $base = $parse($baseline);
    $out = [];
    if ($count($xp, 'script') > $count($base, 'script')) {
        $out[] = 'script';
    }
    foreach (['iframe', 'frame', 'object', 'embed', 'base', 'meta', 'link', 'style', 'math'] as $tag) {
        if ($count($xp, $tag) > $count($base, $tag)) {
            $out[] = 'element:' . $tag;
        }
    }
    $urlAttrs = ['href', 'src', 'srcset', 'action', 'formaction', 'poster', 'cite', 'background', 'xlink:href', 'data', 'ping'];
    $marker = $payload !== '' ? strtolower(substr((string) preg_replace('/[^a-z]/i', '', $payload), 0, 12)) : '';
    foreach ($xp->query('//@*') ?: [] as $a) {
        $name = strtolower($a->nodeName);
        $val = (string) $a->nodeValue;
        $el = $a->parentNode instanceof DOMElement ? strtolower($a->parentNode->nodeName) : '';
        if (str_starts_with($name, 'on')) {
            $out[] = 'on-attr:' . $name;
        }
        if ($marker !== '' && strlen($marker) > 4 && !in_array($name, ['class', 'id'], true)
            && str_contains(strtolower(preg_replace('/[^a-z]/i', '', $name)), $marker)) {
            $out[] = 'attr-name';
        }
        if (in_array($name, $urlAttrs, true)) {
            $candidates = $name === 'srcset' || $name === 'ping' ? preg_split('/,|\s+(?=\S)/', $val) : [$val];
            foreach ($candidates ?: [] as $u) {
                $k = strtolower((string) preg_replace('/[\x00-\x20\x7f]+/', '', (string) $u));
                $img = in_array($el, ['img', 'source'], true) && ($name === 'src' || $name === 'srcset');
                if (preg_match('/^(javascript|vbscript|livescript):/', $k) === 1
                    || (str_starts_with($k, 'data:')
                        && !($img && preg_match('#^data:image/(png|jpeg|gif|webp|avif|svg\+xml)[;,]#', $k) === 1))) {
                    $out[] = 'url:' . $name;
                }
            }
        }
        if ($name === 'style') {
            $css = strtolower((string) preg_replace('/[\x00-\x20\x7f]+|\\\\/', '', $val));
            if (preg_match('/url\(["\']?(javascript|vbscript|livescript|data):|expression\(/', $css) === 1) {
                $out[] = 'style-url';
            }
        }
    }
    if (!str_contains($payload, '1337') && str_contains($html, '1337') && !str_contains($baseline, '1337')) {
        $out[] = 'ssti';
    }
    return array_values(array_unique($out));
};
