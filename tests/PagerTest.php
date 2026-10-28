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
}
