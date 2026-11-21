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

require_once __DIR__.'/fixtures/links-stubs.php';
require_once __DIR__.'/fixtures/rest-stubs.php';
require_once __DIR__.'/fixtures/pager-stubs.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__).'/plugins/wpfasty-builder/src/'.str_replace('\\', '/', substr($class, 15)).'.php';
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
    private const SRC = __DIR__.'/../plugins/wpfasty-builder/sources/ui8kit';

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

    /** link mode (codegen-x 000ee4a6 pageUrl / firstUrl): the adapter's addresses, through Safe; a hostile one is dropped */
    public function testUrlsOfTheLinkMode(): void
    {
        self::assertSame('http://example.test/page/{0}', Pager::template());
        self::assertSame(['url' => 'http://example.test/page/{0}', 'firstUrl' => 'http://example.test/page/1'], Pager::urls());
        $ctx = ['pagination' => ['current' => 2, 'total' => 3, 'url' => '/blog/page/{0}/?s=a', 'firstUrl' => '/blog/?s=a']];
        self::assertSame(['url' => '/blog/page/{0}/?s=a', 'firstUrl' => '/blog/?s=a'], Pager::urls($ctx));
        $bad = Pager::urls(['pagination' => ['url' => 'javascript:alert({0})', 'firstUrl' => '//evil.test/']]);
        self::assertSame(['url' => '', 'firstUrl' => ''], $bad);
        self::assertArrayNotHasKey('url', Pager::value(2, 5, $bad), 'no address: buttons');
        self::assertArrayNotHasKey('url', Pager::value(2, 5, ['url' => '/x', 'firstUrl' => '/']), 'no {0}: buttons');
        self::assertArrayNotHasKey('url', Pager::value(2, 5), 'editor preview: buttons');
        $v = Pager::value(2, 5, Pager::urls());
        self::assertSame('http://example.test/page/{0}', $v['url']);
        self::assertSame('http://example.test/page/1', $v['firstUrl']);
    }

    /** @return array{0: Catalog, 1: BlockRenderer} */
    private static function runtime(string $engine): array
    {
        $src = new LocalSource('ui8kit', self::SRC.'/'.$engine, $engine);

        return [new Catalog($src->catalog()), new BlockRenderer(['ui8kit' => $src], null)];
    }

    /** @return array<string, string> block id => HTML */
    private static function page(string $engine, Catalog $catalog, BlockRenderer $r, array $entry, array $wpfb): array
    {
        $tpl = Compiler::compile($entry, $catalog, $engine);
        $fn = static fn (string $s, string $t, array $p): string => $r->render($s, $t, $p);
        if ('latte' === $engine) {
            $l = new Engine();
            $l->setLoader(new StringLoader(['page' => $tpl]));
            $l->addFunction('wpfb_block', $fn);
            $html = $l->renderToString('page', ['wpfb' => $wpfb]);
        } else {
            $env = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['page' => $tpl]), ['autoescape' => 'html']);
            $env->registerUndefinedFunctionCallback(static fn (string $n): \Twig\TwigFunction|false => 'wpfb_block' === $n ? new \Twig\TwigFunction($n, $fn, ['is_safe' => ['html']]) : false);
            $html = $env->render('page', ['wpfb' => $wpfb]);
        }

        return (require dirname(__DIR__).'/tools/builder/fragment-html.php')['blocks']('<!doctype html><html><body>'.$html.'</body></html>');
    }
}
