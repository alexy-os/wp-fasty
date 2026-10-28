<?php

/**
 * Render-block check: every variant of the bundled catalog (sample props) rendered alone (Fragment, i.e. what
 * POST /wpfasty-builder/v1/render-block returns) must equal the same block inside the full compiled page, after
 * whitespace normalization (tools/builder/fragment-html.php). Both runtimes, isolated.
 *
 *   php tools/builder/render-block-check.php                     offline: Latte and Twig pages compiled and rendered here
 *   php tools/builder/render-block-check.php --live <site-url> --page <id> [--jar <cookie jar>] [--runs 5] [--timing-only]
 *        live: a running WordPress (logged-in cookie jar, e.g. Playground CLI): saves layouts of 40 variants as the
 *        page draft, fetches the preview page, posts every block to /render-block and compares; then times one text
 *        edit both ways (draft save + preview reload vs. render-block). The page draft is restored afterwards.
 *
 * Exit 1 on any difference.
 *
 * @package WPFastyBuilder
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$html = require __DIR__ . '/fragment-html.php';
$args = getopt('', ['live:', 'page:', 'jar:', 'runs:', 'timing-only']);

if (!isset($args['live'])) {
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
    $fail = 0;
    $sample = static fn (array $slot): array => (array) ($slot['sample'] ?? []);
    $pagerOf = static fn (int $pages): ?array => WPFastyBuilder\Pager::value(2, $pages);
    foreach (['latte', 'twig'] as $engine) {
        $src = new WPFastyBuilder\Source\LocalSource('ui8kit', $root . '/plugins/wpfasty-builder/sources/ui8kit/' . $engine, $engine);
        $catalog = new WPFastyBuilder\Catalog($src->catalog());
        $r = new WPFastyBuilder\BlockRenderer(['ui8kit' => $src], null);
        $fn = static fn (string $s, string $t, array $p): string => $r->render($s, $t, $p);
        $all = [];
        foreach ($src->catalog()['blocks'] as $b) {
            foreach ($b['variants'] as $v) {
                $all[] = ['id' => 'v' . count($all), 'block' => $b['id'], 'variant' => $v['id']];
            }
        }
        $same = $bytes = 0;
        $t0 = microtime(true);
        foreach (array_chunk($all, WPFastyBuilder\Layout::MAX_BLOCKS) as $chunk) {
            [$entry] = WPFastyBuilder\Layout::normalize(['blocks' => $chunk], 'ui8kit', $catalog, [], [
                'id' => 'layout-check', 'slug' => 'check', 'title' => 'Check', 'version' => 1,
                'created_at' => '2026-10-24T00:00:00Z', 'updated_at' => '2026-10-24T00:00:00Z',
            ]);
            $tpl = WPFastyBuilder\Compiler::compile($entry, $catalog, $engine);
            // the current record (richtext): the catalog's fixture record, on the page (wpfb) and in the fragment
            $wpfb = [];
            foreach ($chunk as $b) {
                foreach ($catalog->currentValues($b['block'], $b['variant'], $sample) as $path => $value) {
                    $wpfb[$b['id']][$path] = $value;
                }
                // the pager the adapter fills (posts listPaged): page 2 of 5, on the page and in the fragment
                $at = $catalog->pager($b['block'], $b['variant']);
                if ($at !== null) {
                    $props = (array) ($catalog->variant($b['block'], $b['variant'])['props'] ?? []);
                    $wpfb[$b['id']][$at['path']] = WPFastyBuilder\Pager::active($props, $at['path']) ? $pagerOf(5) : null;
                }
            }
            if ($engine === 'latte') {
                $l = new Latte\Engine();
                $l->setLoader(new Latte\Loaders\StringLoader(['page' => $tpl]));
                $l->addFunction('wpfb_block', $fn);
                $page = $l->renderToString('page', ['wpfb' => $wpfb]);
            } else {
                $env = new Twig\Environment(new Twig\Loader\ArrayLoader(['page' => $tpl]), ['autoescape' => 'html']);
                $env->registerUndefinedFunctionCallback(static fn (string $n): Twig\TwigFunction|false
                    => $n === 'wpfb_block' ? new Twig\TwigFunction($n, $fn, ['is_safe' => ['html']]) : false);
                $page = $env->render('page', ['wpfb' => $wpfb]);
            }
            $blocks = $html['blocks']('<!doctype html><html><body>' . $page . '</body></html>');
            foreach ($chunk as $b) {
                $out = WPFastyBuilder\Fragment::render($catalog, 'ui8kit', $b, [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p), $sample, null, static fn (): ?array => $pagerOf(5));
                $frag = $out === null ? null : $html['inner']($out['html']);
                if ($frag === null || ($blocks[$b['id']] ?? null) !== $frag || str_contains($frag, '<!-- wpfasty-builder')) {
                    $fail++;
                    fwrite(STDERR, "✗ $engine {$b['block']}/{$b['variant']}\n");
                    continue;
                }
                $same++;
                $bytes += strlen($frag);
            }
        }
        printf("%s: %d/%d variants — render-block fragment = block in the compiled page (%d KB, %.1f s)\n", $engine, $same, count($all), $bytes / 1024, microtime(true) - $t0);
    }
    echo $fail === 0 ? "render-block: 0 differences\n" : "render-block: $fail difference(s)\n";
    exit($fail === 0 ? 0 : 1);
}

// ---- live mode -------------------------------------------------------------------------------------------------
$base = rtrim((string) $args['live'], '/');
$pageId = (int) ($args['page'] ?? 0);
$jar = (string) ($args['jar'] ?? sys_get_temp_dir() . '/wpfb-check.jar');
$runs = max(1, (int) ($args['runs'] ?? 5));
if ($pageId < 1) {
    fwrite(STDERR, "--page <id> is required\n");
    exit(2);
}
/** @return array{0: int, 1: string, 2: float} status, body, seconds */
$http = static function (string $method, string $url, ?array $json = null, array $headers = []) use ($jar): array {
    $c = curl_init($url);
    curl_setopt_array($c, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_TIMEOUT => 300,
        CURLOPT_HTTPHEADER => array_merge($headers, $json !== null ? ['Content-Type: application/json'] : []),
    ]);
    if ($json !== null) {
        curl_setopt($c, CURLOPT_POSTFIELDS, json_encode($json));
    }
    $t = microtime(true);
    $body = (string) curl_exec($c);
    $dt = microtime(true) - $t;
    return [(int) curl_getinfo($c, CURLINFO_HTTP_CODE), $body, $dt];
};
[$code, $admin] = $http('GET', "$base/wp-admin/edit.php?post_type=page&page=wpfasty-builder&post=$pageId");
if ($code !== 200 || preg_match('/window\.WPFastyBuilderConfig = (\{.*?\});/', $admin, $m) !== 1) {
    fwrite(STDERR, "no editor config at the builder screen (HTTP $code; logged in?)\n");
    exit(2);
}
$cfg = json_decode($m[1], true);
$rest = rtrim((string) $cfg['rest'], '/');
$h = ['X-WP-Nonce: ' . $cfg['nonce']];
$api = static function (string $method, string $path, ?array $json = null) use ($http, $rest, $h): array {
    [$code, $body, $dt] = $http($method, $rest . $path, $json, $h);
    return [$code, json_decode($body, true), $dt];
};
[, $layout] = $api('GET', "/layout/$pageId");
$original = $layout['layout'] ?? null;
$source = (string) ($original['metadata']['source']['value'] ?? 'ui8kit');
[, $catalog] = $api('GET', '/catalog?source=' . rawurlencode($source));
$all = [];
foreach ($catalog['blocks'] as $b) {
    foreach ($b['variants'] as $v) {
        $all[] = ['id' => 'v' . count($all), 'block' => $b['id'], 'variant' => $v['id'], 'props' => (object) [], 'bindings' => (object) []];
    }
}
$fail = $same = 0;
$times = [];
foreach (isset($args['timing-only']) ? [] : array_chunk($all, 40) as $chunk) {
    [$code, $saved] = $api('POST', "/layout/$pageId", ['publish' => false, 'layout' => ['source' => $source, 'blocks' => $chunk]]);
    if ($code !== 200) {
        fwrite(STDERR, "save failed: HTTP $code\n");
        exit(1);
    }
    [, $page] = $http('GET', (string) $saved['previewUrl']);
    $blocks = $html['blocks']($page);
    foreach ($chunk as $b) {
        [$code, $out, $dt] = $api('POST', '/render-block', ['source' => $source, 'page' => $pageId] + $b);
        $times[] = $dt;
        $frag = $code === 200 ? $html['inner']((string) $out['html']) : null;
        if ($frag === null || ($blocks[$b['id']] ?? null) !== $frag) {
            $fail++;
            fwrite(STDERR, "✗ {$b['block']}/{$b['variant']} (HTTP $code)\n");
            continue;
        }
        $same++;
    }
}
isset($args['timing-only']) || printf("%s (%s): %d/%d variants — render-block fragment = block in the preview page\n", $base, $catalog['meta']['runtime'] ?? '?', $same, count($all));

// timing: one text edit on the original layout, both ways
$med = static function (array $xs): float {
    sort($xs);
    return $xs[intdiv(count($xs), 2)] ?? 0.0;
};
$blocksOf = static fn (?array $e): array => is_array($e) ? array_map(static fn ($b): array => (array) $b, (array) ($e['metadata']['blocks']['value'] ?? [])) : [];
$items = $blocksOf($original);
if ($items !== []) {
    $before = $after = [];
    for ($i = 0; $i < $runs; $i++) {
        $items[0]['props'] = array_merge((array) ($items[0]['props'] ?? []), ['title' => "Edited title $i"]);
        $t = microtime(true);
        [, $saved] = $api('POST', "/layout/$pageId", ['publish' => false, 'layout' => ['source' => $source, 'collection' => $original['metadata']['collection']['value'] ?? '', 'blocks' => $items]]);
        $http('GET', (string) $saved['previewUrl']);
        $before[] = microtime(true) - $t;
        $t = microtime(true);
        $api('POST', '/render-block', ['source' => $source, 'page' => $pageId] + $items[0]);
        $after[] = microtime(true) - $t;
    }
    printf("edit → visible (median of %d, %d blocks on the page): save draft + reload preview %.0f ms; render-block %.0f ms (×%.1f)\n", $runs, count($items), $med($before) * 1000, $med($after) * 1000, $med($before) / max(0.001, $med($after)));
    $times === [] || printf("render-block per variant: median %.0f ms, max %.0f ms\n", $med($times) * 1000, max($times) * 1000);
}
// restore the draft the page had (a page without a layout keeps an empty draft; its front end is unchanged)
$api('POST', "/layout/$pageId", ['publish' => false, 'layout' => ['source' => $source, 'collection' => $original['metadata']['collection']['value'] ?? '', 'blocks' => $blocksOf($original)]]);
if ($items === []) {
    echo "no layout on page $pageId: edit timing skipped (use a builder landing)\n";
}
echo $fail === 0 ? "render-block live: 0 differences\n" : "render-block live: $fail difference(s)\n";
exit($fail === 0 ? 0 : 1);
