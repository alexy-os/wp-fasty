<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use Latte\Engine;
use Latte\Loaders\StringLoader;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\BlockRenderer;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Compiler;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Source\LocalSource;
use WPFastyBuilder\Source\RemoteSource;

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__) . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

final class BuilderTest extends TestCase
{
    private const SRC = __DIR__ . '/../plugins/wpfasty-builder/sources/ui8kit';
    private const LATTE = self::SRC . '/latte';
    private const TWIG = self::SRC . '/twig';
    private static ?Catalog $catalog = null;
    private static ?Catalog $twigCatalog = null;

    private static function catalog(): Catalog
    {
        return self::$catalog ??= new Catalog((new LocalSource('ui8kit', self::LATTE, 'latte'))->catalog());
    }

    private static function twigCatalog(): Catalog
    {
        return self::$twigCatalog ??= new Catalog((new LocalSource('ui8kit', self::TWIG, 'twig'))->catalog());
    }

    private static function latteSource(bool $sandboxed = false): LocalSource
    {
        return new LocalSource('ui8kit', self::LATTE, 'latte', '', $sandboxed);
    }

    private static function twigSource(): LocalSource
    {
        return new LocalSource('ui8kit', self::TWIG, 'twig');
    }

    /** a zip of $files (name → content) */
    private static function zip(string $file, array $files): string
    {
        @mkdir(dirname($file), 0777, true);
        $zip = new \ZipArchive();
        $zip->open($file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        return (string) file_get_contents($file);
    }

    /** @return array<string, mixed> */
    private static function base(): array
    {
        return [
            'id' => 'layout-7', 'slug' => 'home', 'title' => 'Home', 'page_id' => 7, 'version' => 1,
            'created_at' => '2026-10-17T01:00:00Z', 'updated_at' => '2026-10-17T01:00:00Z',
        ];
    }

    public function testBundledCatalogIsValid(): void
    {
        $data = (new LocalSource('ui8kit', self::LATTE, 'latte'))->catalog();
        $this->assertSame([], Catalog::check($data));
        $twig = (new LocalSource('ui8kit', self::TWIG, 'twig'))->catalog();
        $this->assertSame([], Catalog::check($twig));
        $this->assertSame(['latte', 'twig'], [$data['runtime'], $twig['runtime']]);
        $this->assertSame(2, $twig['format']);
        // one catalog per runtime: same blocks and variants, each with its own runtime's templates (and files)
        $ids = static fn (array $c): array => array_map(static fn ($b) => [$b['id'], array_column($b['variants'], 'id')], $c['blocks']);
        $this->assertSame($ids($data), $ids($twig));
        $this->assertSame([], Catalog::missingTemplates($data, self::LATTE));
        $this->assertSame([], Catalog::missingTemplates($twig, self::TWIG));
        foreach ($twig['blocks'] as $b) {
            foreach ($b['variants'] as $v) {
                $this->assertStringEndsWith('.html.twig', $v['template']);
            }
        }
        $this->assertNotNull(self::catalog()->variant('posts', 'magazine'));
        $this->assertSame(['posts' => 'post', 'filter.topics' => 'topic'], self::catalog()->bindable('posts', 'grid'));
        $blog = self::catalog()->forEditor('content-blog');
        $this->assertContains('posts', array_column($blog['blocks'], 'id'));
        $this->assertNotContains('cart', array_column($blog['blocks'], 'id'));
    }

    public function testCatalogCheckRejectsBadTemplates(): void
    {
        $bad = ['templates' => 'latte', 'blocks' => [['id' => 'x', 'variants' => [['id' => 'a', 'template' => '../../wp-config.php', 'props' => []]]]]];
        $this->assertNotSame([], Catalog::check($bad));
        $this->assertNotSame([], Catalog::check(null));
        $mixed = ['templates' => 'twig', 'runtime' => 'twig', 'blocks' => [['id' => 'x', 'variants' => [['id' => 'a', 'template' => 'blocks/x.latte', 'props' => []]]]]];
        $this->assertNotSame([], Catalog::check($mixed), 'a twig catalog cannot point to Latte templates');
        $this->assertNotSame([], Catalog::check(['runtime' => 'twig'] + $bad), 'runtime must equal templates');
        $legacy = ['templates' => 'latte', 'engines' => ['latte', 'twig'], 'blocks' => [['id' => 'x', 'variants' => [['id' => 'a', 'template' => 'blocks/x.latte', 'props' => []]]]]];
        $asTwig = Catalog::forRuntime($legacy, 'twig');
        $this->assertSame([], Catalog::check($asTwig));
        $this->assertSame(['twig', 'blocks/x.html.twig'], [$asTwig['runtime'], $asTwig['blocks'][0]['variants'][0]['template']]);
        $this->assertArrayNotHasKey('engines', $asTwig);
    }

    public function testNormalizeSanitizesAndValidates(): void
    {
        [$entry, $issues] = Layout::normalize([
            'collection' => 'nope',
            'blocks' => [
                ['block' => 'pagehead', 'variant' => 'start', 'props' => ['title' => '<script>x</script>Hi', 'href' => 'javascript:alert(1)']],
                ['id' => 'news', 'block' => 'posts', 'variant' => 'grid', 'props' => ['posts' => [['id' => 'stale']]],
                    'bindings' => ['posts' => ['source' => 'posts', 'limit' => 99, 'term' => 'De sign!', 'sale' => 'yes'], 'bad' => ['source' => 'posts']]],
                ['block' => 'missing', 'variant' => 'x'],
                ['id' => 'news', 'block' => 'stats', 'variant' => 'plain4', 'bindings' => ['stats' => ['source' => 'evil']]],
            ],
        ], 'ui8kit', self::catalog(), ['posts'], self::base());
        $codes = array_column($issues, 'code');
        $this->assertContains('layout.binding_path_unknown', $codes);
        $this->assertContains('layout.block_unknown', $codes);
        $this->assertContains('layout.binding_source_unknown', $codes);
        $this->assertSame([], array_filter($codes, static fn (string $c): bool => str_starts_with($c, 'core.')));
        $blocks = Layout::blocks($entry);
        $this->assertCount(3, $blocks);
        $this->assertSame('xHi', $blocks[0]['props']['title']);
        $this->assertSame('#/', $blocks[0]['props']['href']);
        $this->assertSame(['source' => 'posts', 'limit' => 24, 'term' => 'design'], $blocks[1]['bindings']['posts']);
        $this->assertArrayNotHasKey('posts', $blocks[1]['props'], 'bound paths drop their literal value');
        $this->assertSame('newsx', $blocks[2]['id'], 'block ids stay unique');
        $this->assertSame('', $entry['metadata']['collection']['value']);
        $this->assertSame('layout', $entry['kind']);
        $this->assertTrue(Layout::safeHref('{page:about}#team'));
        $this->assertTrue(Layout::safeHref('/shop/'));
        $this->assertFalse(Layout::safeHref('data:text/html,x'));
        [$sale] = Layout::normalize([
            'blocks' => [['id' => 'sale', 'block' => 'products', 'variant' => 'grid3',
                'bindings' => ['products' => ['source' => 'products', 'limit' => 4, 'sale' => true]]]],
        ], 'ui8kit', self::catalog(), ['products'], self::base());
        $this->assertSame(['source' => 'products', 'limit' => 4, 'sale' => true], Layout::blocks($sale)[0]['bindings']['products']);
    }

    public function testEntryCoreChecks(): void
    {
        [$entry] = Layout::normalize(['blocks' => []], 'ui8kit', self::catalog(), [], self::base());
        $this->assertSame([], Layout::check($entry));
        $entry['updated_at'] = '2026-10-16T00:00:00Z';
        $entry['locale'] = 'EN_us';
        $codes = array_column(Layout::check($entry), 'code');
        $this->assertContains('core.timestamps_order', $codes);
        $this->assertContains('core.locale_invalid', $codes);
        $this->assertSame('layout', Layout::manifest()['resources'][0]['record']['id']);
    }

    public function testReadLabelIsKindAware(): void
    {
        $props = ['labels' => ['read' => 'Read the post', 'by' => 'By {0}']];
        $projects = ['props' => [], 'bindings' => ['posts' => ['source' => 'projects']]];
        $this->assertSame('View project', Compiler::readLabel($props, $projects)['labels']['read']);
        $this->assertSame('By {0}', Compiler::readLabel($props, $projects)['labels']['by']);
        $posts = ['props' => [], 'bindings' => ['posts' => ['source' => 'posts']]];
        $this->assertSame('Read the post', Compiler::readLabel($props, $posts)['labels']['read']);
        $own = ['props' => ['labels' => ['read' => 'Case study']], 'bindings' => ['posts' => ['source' => 'projects']]];
        $this->assertSame('Read the post', Compiler::readLabel($props, $own)['labels']['read']);
    }

    public function testLiteralEscapesStrings(): void
    {
        $this->assertSame("['a' => 'It\\'s {x}', 'n' => [1, 2.5, true, null]]", Compiler::literal(['a' => "It's {x}", 'n' => [1, 2.5, true, null]], [], []));
        $this->assertSame("['p' => (\$x)]", Compiler::literal(['p' => [1]], [], ['p' => '$x']));
        $latte = new Engine();
        $latte->setLoader(new StringLoader());
        $value = ['q' => "a'b\\c {\$d} \"e\" */ {* f *}", 'list' => ['x', ['y' => 2]]];
        $out = $latte->renderToString('{=json_encode(' . Compiler::literal($value, [], []) . ')|noescape}');
        $this->assertSame($value, json_decode($out, true));
    }

    public function testTwigCompileRendersTheSameHtmlAsLatte(): void
    {
        [$entry] = Layout::normalize(['blocks' => [
            ['id' => 'intro', 'block' => 'pagehead', 'variant' => 'start', 'props' => ['title' => "It's <Builder> \\ page"]],
            ['id' => 'news', 'block' => 'posts', 'variant' => 'grid', 'bindings' => ['posts' => ['source' => 'posts']]],
            ['id' => 'gone', 'block' => 'nope', 'variant' => 'x'],
        ]], 'ui8kit', self::catalog(), ['posts'], self::base());
        $latte = Compiler::compile($entry, self::catalog());
        $twig = Compiler::compile($entry, self::twigCatalog(), 'twig');
        // no cross-runtime compile: a Latte catalog gives a Twig page nothing to render, and vice versa
        $this->assertStringNotContainsString('wpfb_block', Compiler::compile($entry, self::catalog(), 'twig'));
        $this->assertStringNotContainsString('wpfb_block', Compiler::compile($entry, self::twigCatalog(), 'latte'));
        $this->assertStringStartsWith(Compiler::marker($entry, 'twig'), $twig);
        $this->assertStringContainsString("{{ wpfb_block('ui8kit', 'blocks/post-grid.html.twig'", $twig);
        $this->assertStringContainsString("wpfb['news']['posts'] ?? []", $twig);
        $post = ['id' => 'post-1', 'title' => 'A "real" post', 'excerpt' => 'E', 'category' => 'News', 'author' => 'Ann',
            'date' => 'Oct 1, 2026', 'minutes' => 2, 'icon' => 'file-text', 'href' => 'https://example.test/a/', 'image' => 'https://example.test/a.jpg'];
        $rl = new BlockRenderer(['ui8kit' => self::latteSource()], null);
        $rt = new BlockRenderer(['ui8kit' => self::twigSource()], null);
        $ctx = ['wpfb' => ['news' => ['posts' => [$post]]]];
        $l = new Engine();
        $l->setLoader(new StringLoader(['page' => $latte]));
        $l->addFunction('wpfb_block', static fn (string $s, string $t, array $p): string => $rl->render($s, $t, $p));
        $fn = static fn (string $s, string $t, array $p): string => $rt->render($s, $t, $p);
        $env = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['page' => $twig]), ['autoescape' => 'html']);
        $env->registerUndefinedFunctionCallback(static fn (string $n): \Twig\TwigFunction|false
            => $n === 'wpfb_block' ? new \Twig\TwigFunction($n, $fn, ['is_safe' => ['html']]) : false);
        $strip = require dirname(__DIR__) . '/tools/parity/normalize-dom.php';
        $a = $strip($l->renderToString('page', $ctx));
        $this->assertStringContainsString('A &quot;real&quot; post', $a);
        $this->assertSame($a, $strip($env->render('page', $ctx)));
        $this->assertSame($strip($l->renderToString('page', [])), $strip($env->render('page', [])));
    }

    public function testTwigBlocksNeedNoLatteAndMatchLatteBlocks(): void
    {
        $dom = require dirname(__DIR__) . '/tools/parity/normalize-dom.php';
        $rt = new BlockRenderer(['ui8kit' => self::twigSource()], null);
        $rl = new BlockRenderer(['ui8kit' => self::latteSource()], null);
        $props = (array) self::catalog()->variant('pagehead', 'start')['props'];
        $props['title'] = 'Hi <b> & "q"';
        $html = $rt->render('ui8kit', 'blocks/page-intro.html.twig', $props);
        $this->assertStringContainsString('Hi &lt;b&gt; &amp;', $html);
        $this->assertSame($dom($rl->render('ui8kit', 'blocks/page-intro.latte', $props)), $dom($html));
        $this->assertSame('', $rt->render('ui8kit', '../x.html.twig', []));
        // Isolated runtimes: no source ever renders the other runtime's template — no fallback in either direction.
        $this->assertSame('', $rt->render('ui8kit', 'blocks/page-intro.latte', $props));
        $this->assertSame('', $rl->render('ui8kit', 'blocks/page-intro.html.twig', $props));
        $dir = sys_get_temp_dir() . '/wpfb-notwig-' . getmypid();
        @mkdir($dir . '/blocks', 0777, true);
        file_put_contents($dir . '/blocks/x.latte', '<p>{$p[\'t\']}</p>');
        $only = new BlockRenderer(['n' => new LocalSource('n', $dir, 'latte')], null);
        $this->assertSame('<p>ok</p>', $only->render('n', 'blocks/x.latte', ['t' => 'ok']));
        $this->assertSame('', $only->render('n', 'blocks/x.html.twig', ['t' => 'ok']), 'a Latte-only source has no Twig block');
        $this->assertSame('', $rt->render('ui8kit', 'blocks/no-such-block.html.twig', []));
    }

    public function testTwigSandboxRejectsUnsafeTemplates(): void
    {
        $dir = sys_get_temp_dir() . '/wpfb-twig-guard-' . getmypid();
        @mkdir($dir . '/twig/blocks', 0777, true);
        $cases = [
            "{{ constant('PHP_VERSION') }}" => false, '{% do 1 %}' => false, "{{ ['id']|map(c => c)|join }}" => false,
            '{% macro m() %}{% endmacro %}' => false, "{{ source('blocks/x.html.twig') }}" => false,
            "{{ ui_text('<b>') }}" => true, "{% set a = 1 %}{% if a %}{{ include('blocks/y.html.twig', {p: p}) }}{% endif %}" => true,
        ];
        file_put_contents($dir . '/twig/blocks/y.html.twig', 'y');
        $i = 0;
        foreach ($cases as $tpl => $ok) {
            // one file per case: Twig names compiled classes by template path, not by content
            $name = 'blocks/x' . ++$i . '.html.twig';
            file_put_contents($dir . '/twig/' . $name, $tpl);
            $html = (new BlockRenderer(['e' => new LocalSource('e', $dir . '/twig', 'twig', '', true)], null))->render('e', $name, []);
            $this->assertSame($ok, !str_starts_with($html, '<!-- wpfasty-builder'), $tpl . ' → ' . $html);
        }
    }

    public function testRemoteTwigZipExtractsOnlyTwigTemplates(): void
    {
        $dir = sys_get_temp_dir() . '/wpfb-twigzip-' . getmypid();
        @mkdir($dir, 0777, true);
        $zipFile = $dir . '/twig.zip';
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('twig/blocks/hero.html.twig', '<h1>{{ p.title }}</h1>');
        $zip->addFromString('blocks/evil.latte', '{php}');
        $zip->addFromString('blocks/x.php', '<?php');
        $zip->close();
        RemoteSource::extract($zipFile, $dir . '/twig', 'twig');
        $this->assertFileExists($dir . '/twig/blocks/hero.html.twig');
        $this->assertFileDoesNotExist($dir . '/twig/blocks/evil.latte');
        $this->assertFileDoesNotExist($dir . '/twig/blocks/x.php');
    }

    public function testTwigLiteralRoundTripsJson(): void
    {
        $value = ['a' => [1, 2.5, true, null, "q'\\"], 'b' => [], 'c' => ['x' => ['y' => 'z']], 'd' => -3];
        $env = new \Twig\Environment(new \Twig\Loader\ArrayLoader([
            'x' => '{{ ' . Compiler::literal($value, [], [], true) . '|json_encode|raw }}',
        ]));
        $this->assertSame($value, json_decode($env->render('x'), true));
    }

    public function testCompiledTemplateRendersBoundData(): void
    {
        [$entry] = Layout::normalize(['blocks' => [
            ['id' => 'intro', 'block' => 'pagehead', 'variant' => 'start', 'props' => ['title' => 'Builder page']],
            ['id' => 'news', 'block' => 'posts', 'variant' => 'grid', 'bindings' => ['posts' => ['source' => 'posts']]],
        ]], 'ui8kit', self::catalog(), ['posts'], self::base());
        $tpl = Compiler::compile($entry, self::catalog());
        $this->assertStringContainsString("wpfb_block('ui8kit', 'blocks/post-grid.latte'", $tpl);
        $this->assertStringContainsString("\$wpfb['news']['posts']", $tpl);
        $this->assertStringNotContainsString('post-static-first', $tpl, 'sample records of a bound path are not compiled in');

        $post = ['id' => 'post-1', 'title' => 'A real post', 'excerpt' => 'E', 'category' => 'News', 'author' => 'Ann',
            'date' => 'Oct 1, 2026', 'minutes' => 2, 'icon' => 'file-text', 'href' => 'https://example.test/a/', 'image' => 'https://example.test/a.jpg'];
        foreach ([false, true] as $guarded) {
            $renderer = new BlockRenderer(['ui8kit' => self::latteSource($guarded)], null);
            $theme = new Engine();
            $theme->setStrictTypes(true);
            $theme->setLoader(new StringLoader(['page' => $tpl]));
            $theme->addFunction('wpfb_block', static fn (string $s, string $t, array $p): string => $renderer->render($s, $t, $p));
            $html = $theme->renderToString('page', ['wpfb' => ['news' => ['posts' => [$post]]]]);
            $this->assertStringContainsString('Builder page', $html);
            $this->assertStringContainsString('A real post', $html);
            $this->assertStringContainsString('href="https://example.test/a/"', $html);
            $this->assertStringContainsString('https://example.test/a.jpg', $html);
            $this->assertStringNotContainsString('<!-- wpfasty-builder', $html);
        }
    }

    public function testGuardRejectsUnsafeTemplates(): void
    {
        $dir = sys_get_temp_dir() . '/wpfb-guard-' . getmypid();
        @mkdir($dir . '/latte/blocks', 0777, true);
        $cases = [
            "{=system('id')}" => false, '{php echo 1}' => false, '{=$x->y()}' => false, '{=ui_text(1)|upper}' => false,
            '{do $a = 1}' => false, '{=new \\stdClass}' => false, "{=ui_text('<b>')|noescape}" => true,
        ];
        foreach ($cases as $tpl => $ok) {
            file_put_contents($dir . '/latte/blocks/x.latte', $tpl);
            $r = new BlockRenderer(['e' => new LocalSource('e', $dir . '/latte', 'latte', '', true)], null);
            $thrown = false;
            try {
                $r->engine('e')?->renderToString('blocks/x.latte', ['p' => []]);
            } catch (\Throwable) {
                $thrown = true;
            }
            $this->assertSame(!$ok, $thrown, $tpl);
        }
        $this->assertSame('', (new BlockRenderer([], null))->render('e', '../x.latte', []));
    }

    public function testRemoteImportKeepsOnlyTemplates(): void
    {
        $dir = sys_get_temp_dir() . '/wpfb-remote-' . getmypid();
        $zipFile = $dir . '-src.zip';
        @mkdir(dirname($zipFile), 0777, true);
        $zip = new \ZipArchive();
        $zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('latte/blocks/hero.latte', '<h1>{$p[\'title\']}</h1>');
        $zip->addFromString('latte/php/Evil.php', '<?php echo 1;');
        $zip->addFromString('../escape.latte', 'x');
        $zip->close();
        $catalog = json_encode(['templates' => 'latte', 'label' => 'Remote', 'blocks' => [
            ['id' => 'hero', 'variants' => [['id' => 'a', 'template' => 'blocks/hero.latte', 'props' => ['title' => 'Hi']]]],
        ], 'collections' => []]);
        $fetch = static fn (string $url): string => str_ends_with($url, 'catalog.json') ? (string) $catalog : (string) file_get_contents($zipFile);
        $remote = new RemoteSource('remote', 'https://registry.example/ui', $dir, 'latte', $fetch);
        $this->assertSame(['blocks' => 1, 'collections' => 0, 'runtime' => 'latte', 'version' => ''], $remote->import());
        $this->assertTrue($remote->imported());
        $this->assertFileExists($dir . '/blocks/hero.latte');
        $this->assertFileDoesNotExist($dir . '/php/Evil.php');
        $this->assertFileDoesNotExist(dirname($dir) . '/escape.latte');
        $this->assertTrue($remote->sandboxed());
        $html = (new BlockRenderer(['remote' => $remote], null))->render('remote', 'blocks/hero.latte', ['title' => 'Hi <b>']);
        $this->assertSame('<h1>Hi &lt;b&gt;</h1>', trim($html));

        $badFetch = static fn (string $url): string => '{"templates":"twig","blocks":[]}';
        $this->expectException(\RuntimeException::class);
        (new RemoteSource('bad', 'https://x.example', $dir . '-bad', 'latte', $badFetch))->import();
    }

    public function testRuntimeRegistryImportIsIsolated(): void
    {
        $dir = sys_get_temp_dir() . '/wpfb-reg2-' . getmypid();
        $zip = self::zip($dir . '-src/blocks.zip', [
            'blocks/hero.html.twig' => '<h1>{{ p.title }}</h1>',
            'blocks/hero.latte' => '<h1>{$p[title]}</h1>',
            'php/Evil.php' => '<?php echo 1;',
        ]);
        $catalog = static fn (array $over = []): string => (string) json_encode($over + [
            'format' => 2, 'runtime' => 'twig', 'templates' => 'twig', 'version' => '1.2.0', 'label' => 'Reg',
            'archive' => ['file' => 'blocks.zip', 'sha256' => hash('sha256', $zip)],
            'blocks' => [['id' => 'hero', 'variants' => [['id' => 'a', 'template' => 'blocks/hero.html.twig', 'props' => []]]]],
            'collections' => [],
        ]);
        $fetchOf = static fn (string $cat, string $z): \Closure => static fn (string $url): string
            => str_ends_with($url, 'catalog.json') ? $cat : (str_ends_with($url, 'blocks.zip') ? $z : throw new \RuntimeException('404 ' . $url));
        $twig = new RemoteSource('reg', 'https://registry.example/twig/wpfasty/v1.2.0', $dir, 'twig', $fetchOf($catalog(), $zip));
        $this->assertSame(['blocks' => 1, 'collections' => 0, 'runtime' => 'twig', 'version' => '1.2.0'], $twig->import());
        $this->assertFileExists($dir . '/blocks/hero.html.twig');
        $this->assertFileDoesNotExist($dir . '/blocks/hero.latte', 'the other runtime is never stored');
        $this->assertFileDoesNotExist($dir . '/php/Evil.php');
        $this->assertSame('<h1>Hi &lt;b&gt;</h1>', trim((new BlockRenderer(['reg' => $twig], null))->render('reg', 'blocks/hero.html.twig', ['title' => 'Hi <b>'])));
        $this->assertFalse((new RemoteSource('reg', 'u', $dir, 'latte', $fetchOf('', '')))->imported(), 'a twig copy is not a latte source');

        $fails = [
            'other runtime' => [new RemoteSource('l', 'u', $dir . '-l', 'latte', $fetchOf($catalog(), $zip)), 'serves twig'],
            'bad sha256' => [new RemoteSource('s', 'u', $dir . '-s', 'twig', $fetchOf($catalog(), $zip . 'x')), 'sha256'],
            'missing template' => [new RemoteSource('m', 'u', $dir . '-m', 'twig', $fetchOf($catalog(['blocks' => [['id' => 'x', 'variants' => [['id' => 'a', 'template' => 'blocks/gone.html.twig', 'props' => []]]]]]), $zip)), 'lacks 1'],
            'no archive hash' => [new RemoteSource('h', 'u', $dir . '-h', 'twig', $fetchOf($catalog(['archive' => ['file' => 'blocks.zip']]), $zip)), 'sha256'],
            // format 1 without the site runtime's zip: rejected, never served from latte.zip
            'format 1 without twig.zip' => [new RemoteSource('f', 'u', $dir . '-f', 'twig', static fn (string $url): string
                => str_ends_with($url, 'catalog.json')
                    ? (string) json_encode(['templates' => 'latte', 'blocks' => [['id' => 'hero', 'variants' => [['id' => 'a', 'template' => 'blocks/hero.latte', 'props' => []]]]]])
                    : (str_ends_with($url, 'latte.zip') ? $zip : throw new \RuntimeException('HTTP 404'))), 'no twig package'],
        ];
        foreach ($fails as $case => [$src, $needle]) {
            try {
                $src->import();
                $this->fail($case . ' imported');
            } catch (\RuntimeException $e) {
                $this->assertStringContainsString($needle, $e->getMessage(), $case);
                $this->assertFalse($src->imported(), $case);
            }
        }
    }

    public function testLegacyStoredCopyServesOnlyItsOwnRuntime(): void
    {
        $dir = sys_get_temp_dir() . '/wpfb-legacy-' . getmypid();
        @mkdir($dir . '/latte/blocks', 0777, true);
        file_put_contents($dir . '/latte/blocks/hero.latte', '<h1>{$p[title]}</h1>');
        file_put_contents($dir . '/catalog.json', (string) json_encode(['templates' => 'latte', 'engines' => ['latte'], 'blocks' => [
            ['id' => 'hero', 'variants' => [['id' => 'a', 'template' => 'blocks/hero.latte', 'props' => []]]],
        ]]));
        $nofetch = static fn (string $u): string => throw new \RuntimeException('offline');
        $latte = new RemoteSource('old', 'u', $dir, 'latte', $nofetch);
        $this->assertTrue($latte->imported());
        $this->assertSame('blocks/hero.latte', $latte->catalog()['blocks'][0]['variants'][0]['template']);
        $this->assertSame('<h1>x</h1>', trim((new BlockRenderer(['old' => $latte], null))->render('old', 'blocks/hero.latte', ['title' => 'x'])));
        $twig = new RemoteSource('old', 'u', $dir, 'twig', $nofetch);
        $this->assertFalse($twig->imported(), 'a pre-K7 copy without twig/ is no Twig source');
        $this->assertSame('', (new BlockRenderer(['old' => $twig], null))->render('old', 'blocks/hero.html.twig', ['title' => 'x']));
    }
}
