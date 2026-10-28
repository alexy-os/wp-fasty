<?php

/**
 * XSS / SSTI fuzz of the builder catalog (CI gate). Every variant of the bundled ui8kit catalog (480), Latte and Twig,
 * every string prop of the variant sample, each payload of tools/security/payloads.php (`html` payloads in every
 * string prop, `url` payloads in every prop that may hold a URL). Three paths:
 *   fragment  POST /render-block: the props as layout overrides (Layout::normalize sanitizes them), bound lists and
 *             the current record filled with the payload too (CMS data is not stripped of tags), Fragment::render
 *   raw       the template layer alone: props straight into Front::prepare + BlockRenderer, no layout sanitizing
 *             (what a catalog sample, a resolved token or a bound record reaches the templates as)
 *   page      the compiled page (Compiler::compile → Latte / Twig source with the props as literals), 40 blocks a page:
 *             a prop must never become template code (SSTI) or break out of its literal
 * The HTML is parsed as DOM; a finding is any <script> element, on* attribute, javascript:/vbscript:/data: URL in a
 * URL attribute (data:image/* only in <img src>/srcset) or style url(), a dynamic attribute name carrying the payload,
 * evaluated template syntax (7*191 = 1337 where the payload has no 1337), or Latte and Twig disagreeing on the
 * normalized DOM of the same input. It also counts renders in which the payload reached the output (as text), so a
 * vacuous pass shows. Exit 1 on any finding.
 *
 *   php tools/security/xss-fuzz.php [--mode fragment,raw,page] [--block <id>] [--max N] [--shard i/n] [--json out.json]
 * --shard i/n takes every n-th variant from the i-th (CI runs the shards in parallel).
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

$opt = getopt('', ['block:', 'max:', 'mode:', 'json:', 'shard:']);
$payloads = require __DIR__ . '/payloads.php';
$max = isset($opt['max']) ? max(1, (int) $opt['max']) : 0;
$lists = [
    'html' => $max > 0 ? array_slice($payloads['html'], 0, $max) : $payloads['html'],
    'url' => $max > 0 ? array_slice($payloads['url'], 0, $max) : $payloads['url'],
    // not a string payload: keys the sample does not have (attrs with on* handlers, style, class, tag…), see $extra
    'extra' => ['#extra'],
];
$modes = explode(',', (string) ($opt['mode'] ?? 'fragment,raw,page'));
$onlyBlock = (string) ($opt['block'] ?? '');
$normalize = require $root . '/tools/parity/normalize-dom.php';
$pageBlocks = (require $root . '/tools/builder/fragment-html.php')['blocks'];
$check = require __DIR__ . '/dom-check.php';

/** a prop that may hold a URL, by its key */
$urlKey = static fn (string $k): bool => preg_match('/(href|src|srcset|action|poster|url|image|avatar|logo|cover)$/i', $k) === 1;

/** every string leaf replaced: url-context leaves in the url list, the others in the html list */
$inject = static function (mixed $v, string $payload, bool $url, string $key = '') use (&$inject, $urlKey): mixed {
    if (is_array($v)) {
        foreach ($v as $k => $x) {
            $v[$k] = $inject($x, $payload, $url, is_string($k) ? $k : $key);
        }
        return $v;
    }
    if (is_string($v) && $urlKey($key) === $url) {
        return $payload;
    }
    return $v;
};

/**
 * Keys a layout may add that the sample does not have, on every object of the props: attribute maps with event
 * handlers and javascript: URLs, style with url(javascript:), class / id / name / type breakouts.
 */
$extra = static function (mixed $v) use (&$extra): mixed {
    if (!is_array($v)) {
        return $v;
    }
    foreach ($v as $k => $x) {
        $v[$k] = $extra($x);
    }
    if ($v !== [] && array_is_list($v)) {
        return $v;
    }
    return $v + [
        'attrs' => [
            'onclick' => 'alert(1)', 'onmouseover' => 'alert(1)', 'href' => 'javascript:alert(1)',
            'src' => 'javascript:alert(1)', 'style' => 'background:url(javascript:alert(1))',
            'formaction' => 'javascript:alert(1)', 'srcdoc' => '<script>alert(1)</script>',
        ],
        'attributes' => ['onclick' => 'alert(1)'],
        'rest' => ['onclick' => 'alert(1)'],
        'style' => 'background:url(javascript:alert(1))',
        'class' => '" onmouseover="alert(1)',
        'className' => '" onmouseover="alert(1)',
        'id' => '" onmouseover="alert(1)',
        'tag' => 'script',
        'as' => 'script',
        'type' => '" onmouseover="alert(1)',
        'target' => '" onmouseover="alert(1)',
        'rel' => '" onmouseover="alert(1)',
    ];
};

$prepare = static fn (array $p): array => method_exists(WPFastyBuilder\Front::class, 'prepare')
    ? WPFastyBuilder\Front::prepare($p)
    : WPFastyBuilder\Links::walk($p);

$engines = [];
foreach (['latte', 'twig'] as $engine) {
    $src = new WPFastyBuilder\Source\LocalSource('ui8kit', $root . '/plugins/wpfasty-builder/sources/ui8kit/' . $engine, $engine);
    $r = new WPFastyBuilder\BlockRenderer(['ui8kit' => $src], null);
    $engines[$engine] = [
        'catalog' => new WPFastyBuilder\Catalog($src->catalog()),
        'render' => static fn (string $t, array $p): string => $r->render('ui8kit', $t, $prepare($p)),
    ];
}
$catalogL = $engines['latte']['catalog'];

[$shard, $shards] = isset($opt['shard']) && preg_match('#^(\d+)/(\d+)$#', (string) $opt['shard'], $sm) === 1
    ? [max(1, (int) $sm[1]), max(1, (int) $sm[2])]
    : [1, 1];
$variants = [];
$n = 0;
foreach ($catalogL->forEditor(null)['blocks'] as $b) {
    if ($onlyBlock !== '' && $b['id'] !== $onlyBlock) {
        continue;
    }
    foreach ($b['variants'] as $v) {
        if ($n++ % $shards === $shard - 1) { // shards are 1-based: 1/4 … 4/4 cover every variant once
            $variants[] = [(string) $b['id'], (string) $v['id']];
        }
    }
}

/**
 * The inputs of one variant with $payload: layout overrides, bound records (sample props at the bound paths), the
 * current record (sample, content stays trusted markup of the adapter: the_content is the CMS's).
 */
$inputs = static function (WPFastyBuilder\Catalog $c, string $block, string $variant, string $payload, bool $url) use ($inject, $extra): array {
    $v = (array) $c->variant($block, $variant);
    $props = (array) ($v['props'] ?? []);
    if ($payload === '#extra') {
        $props = $extra($props);
        $bound = [];
        foreach (array_keys($c->bindable($block, $variant)) as $path) {
            $at = $props;
            foreach (explode('.', $path) as $seg) {
                $at = is_array($at) ? ($at[$seg] ?? null) : null;
            }
            $bound[$path] = is_array($at) ? $at : [];
        }
        return [$props, $bound, static fn (array $slot): array => $extra((array) ($slot['sample'] ?? []))];
    }
    $bound = [];
    foreach (array_keys($c->bindable($block, $variant)) as $path) {
        $at = $props;
        foreach (explode('.', $path) as $seg) {
            $at = is_array($at) ? ($at[$seg] ?? null) : null;
        }
        $bound[$path] = $inject(is_array($at) ? $at : [], $payload, $url);
    }
    $current = static function (array $slot) use ($inject, $payload, $url): array {
        $rec = (array) ($slot['sample'] ?? []);
        foreach ($rec as $k => $x) {
            if (!(is_array($x) && array_key_exists('__html', $x))) {
                $rec[$k] = $inject($x, $payload, $url, (string) $k);
            }
        }
        return $rec;
    };
    return [$inject($props, $payload, $url), $bound, $current];
};

$findings = [];
$renders = 0;
$reached = 0;
$t0 = microtime(true);
$add = static function (array $where, array $found) use (&$findings): void {
    foreach ($found as $f) {
        $findings[] = $where + ['finding' => $f];
    }
};

// baselines (sample props): what the findings are compared with (1337 in a sample, script tags of a block)
$baseline = [];
foreach ($variants as [$block, $variant]) {
    foreach ($engines as $name => $e) {
        $c = $e['catalog'];
        [$p, $bound, $current] = $inputs($c, $block, $variant, '', false);
        $out = WPFastyBuilder\Fragment::render(
            $c,
            'ui8kit',
            ['id' => 'b1', 'block' => $block, 'variant' => $variant],
            [],
            static fn (): array => [],
            $e['render'],
            static fn (array $slot): array => (array) ($slot['sample'] ?? []),
        );
        $baseline["$name|$block|$variant"] = $out['html'] ?? '';
    }
}

foreach (['fragment', 'raw'] as $mode) {
    if (!in_array($mode, $modes, true)) {
        continue;
    }
    foreach ($variants as [$block, $variant]) {
        foreach ($lists as $list => $items) {
            foreach ($items as $payload) {
                $norm = [];
                foreach ($engines as $name => $e) {
                    $c = $e['catalog'];
                    [$props, $bound, $current] = $inputs($c, $block, $variant, $payload, $list === 'url');
                    if ($mode === 'fragment') {
                        $kinds = $c->bindable($block, $variant);
                        $out = WPFastyBuilder\Fragment::render(
                            $c,
                            'ui8kit',
                            [
                                'id' => 'b1', 'block' => $block, 'variant' => $variant, 'props' => $props,
                                'bindings' => array_map(static fn (): array => ['source' => 'fuzz'], $kinds),
                            ],
                            ['fuzz'],
                            static function (array $bind, string $kind, ?array $slot) use ($bound, $kinds): array {
                                $path = (string) array_search($kind, $kinds, true);
                                return $bound[$path] ?? [];
                            },
                            $e['render'],
                            $current,
                        );
                        $html = $out['html'] ?? '';
                    } else {
                        $v = (array) $c->variant($block, $variant);
                        $merged = $props;
                        foreach ($bound as $path => $records) {
                            WPFastyBuilder\Layout::set($merged, explode('.', $path), $records);
                        }
                        foreach ($c->currentValues($block, $variant, $current) as $path => $value) {
                            WPFastyBuilder\Layout::set($merged, explode('.', $path), $value);
                        }
                        $html = ($e['render'])((string) $v['template'], $merged);
                    }
                    $renders++;
                    if (str_contains(html_entity_decode($html, ENT_QUOTES | ENT_HTML5), 'alert(1)')) {
                        $reached++;
                    }
                    $add(
                        ['mode' => $mode, 'engine' => $name, 'block' => $block, 'variant' => $variant, 'payload' => $payload],
                        $check($html, $payload, $baseline["$name|$block|$variant"]),
                    );
                    $norm[$name] = $normalize($html);
                }
                if ($norm['latte'] !== $norm['twig']) {
                    $findings[] = [
                        'mode' => $mode, 'engine' => 'latte≠twig', 'block' => $block, 'variant' => $variant,
                        'payload' => $payload, 'finding' => 'parity',
                    ];
                }
            }
        }
    }
}

if (in_array('page', $modes, true)) {
    foreach ($lists as $list => $items) {
        foreach ($items as $payload) {
            $norm = [];
            foreach ($engines as $name => $e) {
                $c = $e['catalog'];
                foreach (array_chunk($variants, WPFastyBuilder\Layout::MAX_BLOCKS) as $ci => $chunk) {
                    $blocks = [];
                    foreach ($chunk as $i => [$block, $variant]) {
                        [$props] = $inputs($c, $block, $variant, $payload, $list === 'url');
                        $blocks[] = ['id' => 'v' . $i, 'block' => $block, 'variant' => $variant, 'props' => $props];
                    }
                    [$entry] = WPFastyBuilder\Layout::normalize(['blocks' => $blocks], 'ui8kit', $c, [], [
                        'id' => 'layout-fuzz', 'slug' => 'fuzz', 'title' => $payload, 'version' => 1,
                        'created_at' => '2026-10-28T00:00:00Z', 'updated_at' => '2026-10-28T00:00:00Z',
                    ]);
                    // the stored entry is trusted only as far as Layout::normalize made it: the props as saved
                    $tpl = WPFastyBuilder\Compiler::compile($entry, $c, $name);
                    $fn = $e['render'];
                    try {
                        if ($name === 'latte') {
                            $l = new Latte\Engine();
                            $l->setLoader(new Latte\Loaders\StringLoader(['page' => $tpl]));
                            $l->addFunction('wpfb_block', static fn (string $s, string $t, array $p): string => $fn($t, $p));
                            $page = $l->renderToString('page', ['wpfb' => []]);
                        } else {
                            $env = new Twig\Environment(new Twig\Loader\ArrayLoader(['page' => $tpl]), ['autoescape' => 'html']);
                            $env->registerUndefinedFunctionCallback(static fn (string $n): Twig\TwigFunction|false => $n === 'wpfb_block'
                                ? new Twig\TwigFunction($n, static fn (string $s, string $t, array $p): string => $fn($t, $p), ['is_safe' => ['html']])
                                : false);
                            $page = $env->render('page', ['wpfb' => []]);
                        }
                    } catch (Throwable $ex) {
                        $findings[] = [
                            'mode' => 'page', 'engine' => $name, 'block' => "chunk $ci", 'variant' => '',
                            'payload' => $payload, 'finding' => 'compile-error: ' . substr($ex->getMessage(), 0, 120),
                        ];
                        continue;
                    }
                    $renders++;
                    $head = substr($page, 0, (int) strpos($page, '<div class="wpfb-page"'));
                    if (trim((string) preg_replace('/\s+/', ' ', $head)) !== '') {
                        $findings[] = [
                            'mode' => 'page', 'engine' => $name, 'block' => "chunk $ci", 'variant' => '',
                            'payload' => $payload, 'finding' => 'page-head-output (title left the template comment)',
                        ];
                    }
                    foreach ($pageBlocks('<!doctype html><html><body>' . $page . '</body></html>') as $id => $inner) {
                        [$block, $variant] = $chunk[(int) substr((string) $id, 1)];
                        $add(
                            ['mode' => 'page', 'engine' => $name, 'block' => $block, 'variant' => $variant, 'payload' => $payload],
                            $check($inner, $payload, $baseline["$name|$block|$variant"]),
                        );
                        $norm["$block|$variant"][$name] = $normalize($inner);
                    }
                }
            }
            foreach ($norm as $key => $pair) {
                if (($pair['latte'] ?? null) !== ($pair['twig'] ?? null)) {
                    [$block, $variant] = explode('|', $key);
                    $findings[] = [
                        'mode' => 'page', 'engine' => 'latte≠twig', 'block' => $block, 'variant' => $variant,
                        'payload' => $payload, 'finding' => 'parity',
                    ];
                }
            }
        }
    }
}

$by = [];
foreach ($findings as $f) {
    $by[$f['mode'] . ' ' . $f['finding']][] = $f;
}
ksort($by);
foreach ($by as $kind => $list) {
    $ex = $list[0];
    $blocks = count(array_unique(array_map(static fn (array $f): string => $f['block'] . '/' . $f['variant'], $list)));
    fwrite(STDERR, sprintf(
        "✗ %s × %d (%d variants) — e.g. %s/%s [%s] %s\n",
        $kind,
        count($list),
        $blocks,
        $ex['block'],
        $ex['variant'],
        $ex['engine'],
        json_encode($ex['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ));
}
if (isset($opt['json'])) {
    file_put_contents((string) $opt['json'], json_encode($findings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}
fwrite(STDOUT, sprintf(
    "xss-fuzz [%s]%s: %d variants × %d payloads × 2 engines — %d renders, payload reached the output in %d, %.1f s — %d finding(s)\n",
    implode(',', $modes),
    $shards > 1 ? " shard $shard/$shards" : '',
    count($variants),
    array_sum(array_map('count', $lists)),
    $renders,
    $reached,
    microtime(true) - $t0,
    count($findings),
));
exit($findings === [] ? 0 : 1);
