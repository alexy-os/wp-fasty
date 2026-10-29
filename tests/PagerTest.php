<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use Latte\Engine;
use Latte\Loaders\StringLoader;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\BlockRenderer;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Compiler;
use WPFastyBuilder\Fragment;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Pager;
use WPFastyBuilder\Source\LocalSource;

require_once __DIR__ . '/fixtures/links-stubs.php';
require_once __DIR__ . '/fixtures/rest-stubs.php';
require_once __DIR__ . '/fixtures/pager-stubs.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__) . '/plugins/wpfasty-builder/src/' . str_replace('\\', '/', substr($class, 15)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

/**
 * The pager of the posts block (codegen-x PostGrid `pager`, variant listPaged): the WordPress adapter fills the whole
 * group (page, pages, translatable texts), a layout sets only its mode; no pager on one page; the previous / next
 * buttons always have a name; nav landmark + aria-current; Latte = Twig; fragment = page.
 */
final class PagerTest extends TestCase
{
    private const SRC = __DIR__ . '/../plugins/wpfasty-builder/sources/ui8kit';

    /** @return array{0: Catalog, 1: BlockRenderer} */
    private static function runtime(string $engine): array
    {
        $src = new LocalSource('ui8kit', self::SRC . '/' . $engine, $engine);
        return [new Catalog($src->catalog()), new BlockRenderer(['ui8kit' => $src], null)];
    }

    /** @return array<string, string> block id => HTML */
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

    public function testCatalogDeclaresThePagerOfThePostsBlock(): void
    {
        foreach (['latte', 'twig'] as $engine) {
            [$catalog] = self::runtime($engine);
            self::assertSame(['path' => 'pager', 'list' => 'posts'], $catalog->pager('posts', 'listPaged'), $engine);
            self::assertSame(['path' => 'pager', 'list' => 'posts'], $catalog->pager('posts', 'grid'), $engine);
            self::assertNull($catalog->pager('richtext', 'narrow'), $engine);
            self::assertNull($catalog->pager('posts', 'nope'), $engine);
            $props = (array) $catalog->variant('posts', 'listPaged')['props'];
            self::assertTrue(Pager::active($props, 'pager'));
            self::assertFalse(Pager::active((array) $catalog->variant('posts', 'list')['props'], 'pager'));
        }
    }

    public function testValueTextsAndOnePage(): void
    {
        self::assertNull(Pager::value(1, 1));
        self::assertNull(Pager::value(1, 0));
        $v = Pager::value(2, 5);
        self::assertSame(['mode' => 'pages', 'page' => 2, 'pages' => 5], array_slice($v, 0, 3));
        foreach (['label', 'summary', 'previous', 'next', 'pageLabel'] as $k) {
            self::assertNotSame('', trim((string) $v[$k]), "$k is never empty");
        }
        self::assertStringContainsString('{0}', $v['pageLabel']);
        self::assertSame([3, 7], Pager::main(['pagination' => ['current' => 3, 'total' => 7]]));
        self::assertSame(1, Pager::request());
        $links = Pager::links(2, 3);
        self::assertSame('http://example.test/page/1', $links['previous']);
        self::assertSame('http://example.test/page/3', $links['next']);
        self::assertSame(['previous' => null, 'next' => 'http://example.test/page/2'], Pager::links(1, 3));
        self::assertSame(['previous' => 'http://example.test/page/2', 'next' => null], Pager::links(3, 3));
    }

    public function testLayoutKeepsOnlyTheMode(): void
    {
        [$catalog] = self::runtime('latte');
        $norm = static function (array $props) use ($catalog): array {
            [$entry] = Layout::normalize(['blocks' => [['id' => 'p', 'block' => 'posts', 'variant' => 'listPaged', 'props' => $props]]], 'ui8kit', $catalog, [], ['id' => 'l', 'slug' => 'l', 'title' => 'L']);
            return (array) Layout::blocks($entry)[0]['props'];
        };
        self::assertSame(['title' => 'T', 'pager' => ['mode' => 'none']], $norm(['title' => 'T', 'pager' => ['mode' => 'none', 'page' => 9, 'pages' => 99, 'previous' => '', 'next' => '']]));
        self::assertSame([], $norm(['pager' => ['mode' => 'evil', 'label' => 'x']]));
        self::assertSame([], $norm(['pager' => 'x']));
    }

    /** the pager under the list: nav landmark with a name, the current page, named arrows; Latte = Twig; fragment = page */
    public function testPagerRendersLatteEqualsTwigAndFragment(): void
    {
        $dom = require dirname(__DIR__) . '/tools/parity/normalize-dom.php';
        $html = require dirname(__DIR__) . '/tools/builder/fragment-html.php';
        $by = [];
        foreach (['latte', 'twig'] as $engine) {
            [$catalog, $r] = self::runtime($engine);
            $blocks = [
                ['id' => 'paged', 'block' => 'posts', 'variant' => 'listPaged'],
                ['id' => 'one', 'block' => 'posts', 'variant' => 'listPaged'],
                ['id' => 'off', 'block' => 'posts', 'variant' => 'listPaged', 'props' => ['pager' => ['mode' => 'none']]],
            ];
            [$entry] = Layout::normalize(['blocks' => $blocks], 'ui8kit', $catalog, [], ['id' => 'l', 'slug' => 'l', 'title' => 'L']);
            $tpl = Compiler::compile($entry, $catalog, $engine);
            self::assertStringContainsString("wpfb['paged']['pager']", $tpl, "$engine: the page reads the pager");
            $page = self::page($engine, $catalog, $r, $entry, [
                'paged' => ['pager' => Pager::value(2, 5)],
                'one' => ['pager' => Pager::value(1, 1)],
                'off' => ['pager' => null],
            ]);
            $one = $page['paged'];
            self::assertSame(1, preg_match('#<nav[^>]*aria-label="Pages of posts"[^>]*data-testid="posts-pager"|<nav[^>]*data-testid="posts-pager"[^>]*aria-label="Pages of posts"#', $one), "$engine: named nav");
            self::assertSame(1, preg_match('#aria-current="page"[^>]*>\s*2\s*<#', $one), "$engine: page 2 is current");
            self::assertStringContainsString('Page 2 of 5', $one);
            self::assertStringContainsString('aria-label="Previous page"', $one);
            self::assertStringContainsString('aria-label="Next page"', $one);
            self::assertStringContainsString('aria-label="Page 3"', $one);
            self::assertStringNotContainsString('aria-label=""', $one, "$engine: no button without a name");
            self::assertStringNotContainsString('posts-pager', $page['one'], "$engine: no pager on one page");
            self::assertStringNotContainsString('posts-pager', $page['off'], "$engine: mode none");
            $frag = Fragment::render($catalog, 'ui8kit', $blocks[0], [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p), null, null, static fn (int $pages): ?array => Pager::value(2, 5));
            self::assertSame($one, $html['inner']($frag['html']), "$engine: fragment = page");
            $none = Fragment::render($catalog, 'ui8kit', $blocks[0], [], static fn (): array => [], static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p), null, null, static fn (int $pages): ?array => Pager::value(1, $pages));
            self::assertStringNotContainsString('posts-pager', $none['html'], "$engine: editor, one page");
            $by[$engine] = $dom($one);
        }
        self::assertSame($by['latte'], $by['twig'], 'posts/listPaged: Latte = Twig');
    }

    /** link mode (codegen-x 000ee4a6 pageUrl / firstUrl): the adapter's addresses, through Safe; a hostile one is dropped */
    public function testUrlsOfTheLinkMode(): void
    {
        self::assertSame('http://example.test/page/{0}', Pager::template());
        self::assertSame(['url' => 'http://example.test/page/{0}', 'firstUrl' => 'http://example.test/page/1'], Pager::urls());
        $ctx = ['pagination' => ['current' => 2, 'total' => 3, 'url' => '/blog/page/{0}/?s=a', 'first_url' => '/blog/?s=a']];
        self::assertSame(['url' => '/blog/page/{0}/?s=a', 'firstUrl' => '/blog/?s=a'], Pager::urls($ctx));
        $bad = Pager::urls(['pagination' => ['url' => 'javascript:alert({0})', 'first_url' => '//evil.test/']]);
        self::assertSame(['url' => '', 'firstUrl' => ''], $bad);
        self::assertArrayNotHasKey('url', Pager::value(2, 5, $bad), 'no address: buttons');
        self::assertArrayNotHasKey('url', Pager::value(2, 5, ['url' => '/x', 'firstUrl' => '/']), 'no {0}: buttons');
        self::assertArrayNotHasKey('url', Pager::value(2, 5), 'editor preview: buttons');
        $v = Pager::value(2, 5, Pager::urls());
        self::assertSame('http://example.test/page/{0}', $v['url']);
        self::assertSame('http://example.test/page/1', $v['firstUrl']);
    }

    /** links mode renders <a href> for every page and previous / next, Latte = Twig */
    public function testPagerLinksRender(): void
    {
        $dom = require dirname(__DIR__) . '/tools/parity/normalize-dom.php';
        $by = [];
        foreach (['latte', 'twig'] as $engine) {
            [$catalog, $r] = self::runtime($engine);
            $blocks = [['id' => 'paged', 'block' => 'posts', 'variant' => 'listPaged']];
            [$entry] = Layout::normalize(['blocks' => $blocks], 'ui8kit', $catalog, [], ['id' => 'l', 'slug' => 'l', 'title' => 'L']);
            $one = self::page($engine, $catalog, $r, $entry, ['paged' => ['pager' => Pager::value(2, 5, ['url' => '/j/page/{0}/', 'firstUrl' => '/j/'])]])['paged'];
            foreach (['/j/', '/j/page/3/', '/j/page/5/'] as $href) {
                self::assertStringContainsString('href="' . $href . '"', $one, "$engine: $href");
            }
            self::assertStringNotContainsString('href="/j/page/1/"', $one, "$engine: page 1 is firstUrl");
            self::assertStringNotContainsString('href="/j/page/2/"', $one, "$engine: the current page is no link");
            self::assertSame(1, preg_match('#<a[^>]*aria-label="Next page"|<a[^>]*href="/j/page/3/"[^>]*aria-label="Next page"#', $one) | preg_match('#aria-label="Next page"[^>]*href="/j/page/3/"#', $one), "$engine: next is a link");
            $by[$engine] = $dom($one);
        }
        self::assertSame($by['latte'], $by['twig'], 'pager links: Latte = Twig');
    }
}
