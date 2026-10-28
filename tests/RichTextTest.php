<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use Latte\Engine;
use Latte\Loaders\StringLoader;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\BlockRenderer;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Codex\ReadPath;
use WPFastyBuilder\Compiler;
use WPFastyBuilder\Fragment;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Source\LocalSource;

require_once __DIR__ . '/fixtures/links-stubs.php';
require_once __DIR__ . '/fixtures/rest-stubs.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__) . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

/**
 * The richtext block (codegen-x RichText, data scope current): its title, body, cover, meta line and side note body
 * come from the record of the current route — never from the layout — and the body is trusted markup
 * ({"__html": …}, `content|html`) printed by og_html without escaping; any other value is escaped. Latte = Twig.
 */
final class RichTextTest extends TestCase
{
    private const SRC = __DIR__ . '/../plugins/wpfasty-builder/sources/ui8kit';
    private const VARIANTS = ['narrow', 'wide', 'sidebar', 'media', 'card', 'heroTitle'];
    private const BODY = '<h2>Heading</h2><p>A <a href="/x">link</a> &amp; <code>code</code>.</p>'
        . '<figure class="wp-caption alignwide"><img src="/a.jpg" alt="A"><figcaption class="wp-caption-text">C</figcaption></figure>';

    /** @return array{0: Catalog, 1: BlockRenderer} */
    private static function runtime(string $engine): array
    {
        $src = new LocalSource('ui8kit', self::SRC . '/' . $engine, $engine);
        return [new Catalog($src->catalog()), new BlockRenderer(['ui8kit' => $src], null)];
    }

    /** the record the WordPress adapter (DataSources::current) gives for a page */
    private static function record(): array
    {
        return [
            'id' => 'page-7', 'title' => 'About <us>', 'content' => ['__html' => self::BODY], 'date' => 'Oct 1, 2026',
            'author' => 'Ann', 'image' => 'https://example.test/c.jpg', 'aside' => ['__html' => '<p>Side <em>note</em></p>'],
        ];
    }

    /** @return array<string, string> block id => HTML of the compiled page of the six variants */
    private static function page(string $engine, Catalog $catalog, BlockRenderer $r, array $entry, array $wpfb): array
    {
        $tpl = Compiler::compile($entry, $catalog, $engine);
        $fn = static fn (string $s, string $t, array $p): string => $r->render($s, $t, $p);
        if ($engine === 'latte') {
            $l = new Engine();
            $l->setLoader(new StringLoader(['page' => $tpl]));
            $l->addFunction('wpfb_block', $fn);
            $html = $l->renderToString('page', ['wpfb' => $wpfb]);
        } else {
            $env = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['page' => $tpl]), ['autoescape' => 'html']);
            $env->registerUndefinedFunctionCallback(static fn (string $n): \Twig\TwigFunction|false
                => $n === 'wpfb_block' ? new \Twig\TwigFunction($n, $fn, ['is_safe' => ['html']]) : false);
            $html = $env->render('page', ['wpfb' => $wpfb]);
        }
        return (require dirname(__DIR__) . '/tools/builder/fragment-html.php')['blocks']('<!doctype html><html><body>' . $html . '</body></html>');
    }

    public function testCatalogDeclaresTheCurrentRecord(): void
    {
        foreach (['latte', 'twig'] as $engine) {
            [$catalog] = self::runtime($engine);
            $b = $catalog->block('richtext');
            self::assertNotNull($b, $engine);
            self::assertSame('page', $b['group']);
            self::assertSame(self::VARIANTS, array_column($b['variants'], 'id'));
            $slot = $catalog->currentSlot('richtext', 'entry');
            self::assertSame('page', $slot['kind']);
            self::assertSame('content|html', $slot['fields']['content']);
            self::assertSame('metadata.aside.value|html', $slot['fields']['aside']);
            self::assertStringContainsString('<h2>', $slot['sample']['content']['__html']);
            foreach (self::VARIANTS as $v) {
                $cur = $catalog->current('richtext', $v);
                self::assertSame(['data' => 'entry', 'field' => 'content'], $cur['content']);
                self::assertSame(['data' => 'entry', 'field' => 'title'], $cur['title']);
                self::assertSame(['data' => 'entry', 'field' => 'aside'], $cur['aside.content']);
                self::assertSame([], $catalog->bindable('richtext', $v), 'the current record is not a binding');
                // the variant sample props hold no current path (the page fills them)
                $props = (array) $catalog->variant('richtext', $v)['props'];
                self::assertArrayNotHasKey('content', $props);
                self::assertArrayNotHasKey('title', $props);
            }
            self::assertSame(1, $catalog->variant('richtext', 'heroTitle')['props']['level']);
            // a list block's slot of kind page is not the current-scope slot
            self::assertNotSame('RichEntry', $catalog->slotsByKind()['page']['record'] ?? null);
        }
    }

    public function testLayoutNeverCarriesTheCurrentPathsOrTrustedMarkup(): void
    {
        [$catalog] = self::runtime('latte');
        [$entry] = Layout::normalize(['blocks' => [[
            'id' => 'rt', 'block' => 'richtext', 'variant' => 'narrow',
            'props' => ['title' => 'Mine', 'content' => ['__html' => '<script>x</script>'], 'tone' => 'muted',
                'aside' => ['title' => 'Note', 'content' => 'x'], 'extra' => ['__html' => '<i>i</i>', 'k' => 'v']],
        ]]], 'ui8kit', $catalog, [], ['id' => 'l', 'slug' => 'l', 'title' => 'L']);
        $props = (array) Layout::blocks($entry)[0]['props'];
        self::assertSame(['tone' => 'muted', 'aside' => ['title' => 'Note'], 'extra' => ['k' => 'v']], $props);
        // catalog props with a marker (a remote source) are plain text
        self::assertSame(['a' => '<b>x</b>', 'n' => ['b' => 'y']], Layout::untrust(['a' => ['__html' => '<b>x</b>'], 'n' => ['b' => 'y']]));
    }

    public function testHtmlPipeMarksTrustedMarkup(): void
    {
        self::assertSame(['__html' => '<p>x</p>'], ReadPath::read(['content' => '<p>x</p>'], 'content|html'));
        self::assertSame('<p>x</p>', ReadPath::read(['content' => '<p>x</p>'], 'content'));
        self::assertNull(ReadPath::read(['content' => null], 'content|html'));
    }

    /** Latte = Twig (DOM), the fragment = the block in the compiled page, the body raw, the rest escaped, one h1 */
    public function testSixVariantsLatteEqualsTwigAndFragment(): void
    {
        $dom = require dirname(__DIR__) . '/tools/parity/normalize-dom.php';
        $html = require dirname(__DIR__) . '/tools/builder/fragment-html.php';
        $pages = [];
        foreach (['latte', 'twig'] as $engine) {
            [$catalog, $r] = self::runtime($engine);
            $blocks = array_map(static fn (string $v): array => ['id' => 'rt-' . strtolower($v), 'block' => 'richtext', 'variant' => $v], self::VARIANTS);
            [$entry] = Layout::normalize(['blocks' => $blocks], 'ui8kit', $catalog, [], ['id' => 'l', 'slug' => 'l', 'title' => 'L']);
            $rec = static fn (array $slot): array => self::record();
            $wpfb = [];
            foreach ($blocks as $b) {
                foreach ($catalog->currentValues('richtext', $b['variant'], $rec) as $path => $value) {
                    $wpfb[$b['id']][$path] = $value;
                }
            }
            $page = self::page($engine, $catalog, $r, $entry, $wpfb);
            foreach ($blocks as $b) {
                $frag = Fragment::render($catalog, 'ui8kit', $b, [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p), $rec);
                self::assertSame($page[$b['id']], $html['inner']($frag['html']), "$engine {$b['variant']}: fragment = page");
                $one = $page[$b['id']];
                self::assertStringContainsString('<h2>Heading</h2>', $one, "$engine {$b['variant']}: body is markup");
                self::assertStringContainsString('About &lt;us&gt;', $one, "$engine {$b['variant']}: title escaped");
                self::assertStringNotContainsString('<us>', $one);
                self::assertSame($b['variant'] === 'heroTitle' ? 1 : 0, substr_count($one, '<h1'), "$engine {$b['variant']}: h1");
                self::assertSame($b['variant'] === 'sidebar' ? 1 : 0, substr_count($one, '<em>note</em>'), "$engine {$b['variant']}: side note");
                $pages[$b['variant']][$engine] = $dom($one);
            }
        }
        foreach ($pages as $v => $by) {
            self::assertSame($by['latte'], $by['twig'], "richtext/$v: Latte = Twig");
        }
    }

    public function testPlainStringBodyIsEscaped(): void
    {
        foreach (['latte', 'twig'] as $engine) {
            [$catalog, $r] = self::runtime($engine);
            $out = Fragment::render($catalog, 'ui8kit', ['id' => 'rt', 'block' => 'richtext', 'variant' => 'wide'], [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p), static fn (array $s): array => ['title' => 'T', 'content' => '<script>x</script>']);
            self::assertStringContainsString('&lt;script&gt;x&lt;/script&gt;', $out['html'], $engine);
            self::assertStringNotContainsString('<script>', $out['html'], $engine);
            // no record (no queried post of a Codex kind): no body, no heading, still valid
            $none = Fragment::render($catalog, 'ui8kit', ['id' => 'rt', 'block' => 'richtext', 'variant' => 'wide'], [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p), static fn (array $s): array => []);
            self::assertStringNotContainsString('richtext-content', $none['html'], $engine);
            self::assertStringNotContainsString('wpfasty-builder:', $none['html'], $engine);
        }
    }
}
