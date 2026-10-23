<?php

/**
 * Parity normalizer of the UI8Kit 3 pilot (themes/latte on UI8Kit 3 + kit presets, Twig still on the legacy line):
 * every class attribute is canonicalized with the UI8Kit 3 tailwind-merge (`\UI8Kit\Latte\CnMerge`) on BOTH sides,
 * then compared as DOM trees (normalize-dom.php: attributes and class tokens sorted, text trimmed). Tags, attributes,
 * hrefs and text must still be identical; only class lists equal after the merge are accepted. The UI8Kit 3 Latte
 * primitives merge classes and print children on their own lines, the legacy Twig primitives join them verbatim.
 * Drop this once the Twig side moves to UI8Kit 3 (then compare strictly again).
 *
 * @package Latte
 */

declare(strict_types=1);

return static function (string $html): string {
    static $dom = null;
    $dom ??= require __DIR__ . '/normalize-dom.php';
    $runtime = dirname(__DIR__, 2) . '/themes/latte/ui8kit/runtime/CnMerge.php';
    if (!class_exists(\UI8Kit\Latte\CnMerge::class) && is_file($runtime)) {
        require_once $runtime;
    }
    if (!class_exists(\UI8Kit\Latte\CnMerge::class)) {
        throw new \RuntimeException('UI8Kit 3 runtime missing: npx ui8kit@3 add --all; composer dump-autoload');
    }
    $html = (string) preg_replace_callback('/(\sclass=")([^"]*)(")/', static fn (array $m): string
        => $m[1] . \UI8Kit\Latte\CnMerge::merge([html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5)]) . $m[3], $html);

    return $dom($html);
};
