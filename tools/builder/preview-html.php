<?php

/**
 * HTML of every variant of the bundled ui8kit catalog (Latte, sample props), as POST /wpfasty-builder/v1/render-block
 * returns it (Fragment::render), for the preview shots of tools/builder/previews.ts. Offline, like
 * render-block-check.php. Prints one JSON object: {"<block>/<variant>": "<html>", ...} in catalog order.
 *
 *   php tools/builder/preview-html.php > variants.json
 *
 * @package WPFastyBuilder
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require $root . '/themes/latte/vendor/autoload.php';
require $root . '/vendor/autoload.php';
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        require $root . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
    }
});
if (!function_exists('__')) {
    function __(string $s, string $d = ''): string
    {
        return $s;
    }
}

$src = new WPFastyBuilder\Source\LocalSource('ui8kit', $root . '/plugins/wpfasty-builder/sources/ui8kit/latte', 'latte');
$catalog = new WPFastyBuilder\Catalog($src->catalog());
$r = new WPFastyBuilder\BlockRenderer(['ui8kit' => $src], null);
$out = [];
foreach ($src->catalog()['blocks'] as $b) {
    foreach ($b['variants'] as $v) {
        $res = WPFastyBuilder\Fragment::render(
            $catalog,
            'ui8kit',
            ['id' => 'preview', 'block' => $b['id'], 'variant' => $v['id']],
            [],
            static fn (): array => [],
            static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p),
        );
        if ($res === null) {
            fwrite(STDERR, "✗ {$b['id']}/{$v['id']}: no fragment\n");
            exit(1);
        }
        $out[$b['id'] . '/' . $v['id']] = $res['html'];
    }
}
echo json_encode($out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), "\n";
