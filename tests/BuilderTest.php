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
    private static ?Catalog $catalog = null;

    private static function catalog(): Catalog
    {
        return self::$catalog ??= new Catalog((new LocalSource('ui8kit', self::SRC))->catalog());
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
        $data = (new LocalSource('ui8kit', self::SRC))->catalog();
        $this->assertSame([], Catalog::check($data));
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
        $twig = Compiler::compile($entry, self::catalog(), 'twig');
        $this->assertStringStartsWith(Compiler::marker($entry, 'twig'), $twig);
        $this->assertStringContainsString("{{ wpfb_block('ui8kit', 'blocks/post-grid.html.twig'", $twig);
        $this->assertStringContainsString("wpfb['news']['posts'] ?? []", $twig);
        $post = ['id' => 'post-1', 'title' => 'A "real" post', 'excerpt' => 'E', 'category' => 'News', 'author' => 'Ann',
            'date' => 'Oct 1, 2026', 'minutes' => 2, 'icon' => 'file-text', 'href' => 'https://example.test/a/', 'image' => 'https://example.test/a.jpg'];
        $renderer = new BlockRenderer(['ui8kit' => new LocalSource('ui8kit', self::SRC, '', false)], null);
        $fn = static fn (string $s, string $t, array $p): string => $renderer->render($s, $t, $p);
        $ctx = ['wpfb' => ['news' => ['posts' => [$post]]]];
        $l = new Engine();
        $l->setLoader(new StringLoader(['page' => $latte]));
        $l->addFunction('wpfb_block', $fn);
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
        $r = new BlockRenderer(['ui8kit' => new LocalSource('ui8kit', self::SRC, '', false)], null);
        $props = (array) self::catalog()->variant('pagehead', 'start')['props'];
        $props['title'] = 'Hi <b> & "q"';
        $html = $r->render('ui8kit', 'blocks/page-intro.html.twig', $props);
        $this->assertStringContainsString('Hi &lt;b&gt; &amp;', $html);
        $this->assertSame($dom($r->render('ui8kit', 'blocks/page-intro.latte', $props)), $dom($html));
        $this->assertSame('', $r->render('ui8kit', '../x.html.twig', []));
        // A source without a Twig package renders its Latte template under a Twig page.
        $dir = sys_get_temp_dir() . '/wpfb-notwig-' . getmypid();
        @mkdir($dir . '/latte/blocks', 0777, true);
        file_put_contents($dir . '/latte/blocks/x.latte', '<p>{$p[\'t\']}</p>');
        $this->assertSame('<p>ok</p>', (new BlockRenderer(['n' => new LocalSource('n', $dir)], null))->render('n', 'blocks/x.html.twig', ['t' => 'ok']));
    }

    public function testTwigSandboxRejectsUnsafeTemplates(): void
    {
        $dir = sys_get_temp_dir() . '/wpfb-twig-guard-' . getmypid();
        @mkdir($dir . '/latte/blocks', 0777, true);
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
            $html = (new BlockRenderer(['e' => new LocalSource('e', $dir, '', true)], null))->render('e', $name, []);
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
            $renderer = new BlockRenderer(['ui8kit' => new LocalSource('ui8kit', self::SRC, '', $guarded)], null);
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
            $r = new BlockRenderer(['e' => new LocalSource('e', $dir, '', true)], null);
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
        $remote = new RemoteSource('remote', 'https://registry.example/ui', $dir, $fetch);
        $this->assertSame(['blocks' => 1, 'collections' => 0], $remote->import());
        $this->assertTrue($remote->imported());
        $this->assertFileExists($dir . '/latte/blocks/hero.latte');
        $this->assertFileDoesNotExist($dir . '/latte/php/Evil.php');
        $this->assertFileDoesNotExist(dirname($dir) . '/escape.latte');
        $this->assertTrue($remote->sandboxed());
        $html = (new BlockRenderer(['remote' => $remote], null))->render('remote', 'blocks/hero.latte', ['title' => 'Hi <b>']);
        $this->assertSame('<h1>Hi &lt;b&gt;</h1>', trim($html));

        $badFetch = static fn (string $url): string => '{"templates":"twig","blocks":[]}';
        $this->expectException(\RuntimeException::class);
        (new RemoteSource('bad', 'https://x.example', $dir . '-bad', $badFetch))->import();
    }
}
