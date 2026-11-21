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
use WPFastyBuilder\Layout;
use WPFastyBuilder\Source\LocalSource;

require_once __DIR__.'/fixtures/links-stubs.php';
require_once __DIR__.'/fixtures/rest-stubs.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'WPFastyBuilder\\')) {
        $file = dirname(__DIR__).'/plugins/wpfasty-builder/src/'.str_replace('\\', '/', substr($class, 15)).'.php';
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
    private const SRC = __DIR__.'/../plugins/wpfasty-builder/sources/ui8kit';
    private const VARIANTS = ['narrow', 'wide', 'sidebar', 'media', 'card', 'heroTitle'];
    private const BODY = '<h2>Heading</h2><p>A <a href="/x">link</a> &amp; <code>code</code>.</p>'
        .'<figure class="wp-caption alignwide"><img src="/a.jpg" alt="A"><figcaption class="wp-caption-text">C</figcaption></figure>';

    public function testHtmlPipeMarksTrustedMarkup(): void
    {
        self::assertSame(['__html' => '<p>x</p>'], ReadPath::read(['content' => '<p>x</p>'], 'content|html'));
        self::assertSame('<p>x</p>', ReadPath::read(['content' => '<p>x</p>'], 'content'));
        self::assertNull(ReadPath::read(['content' => null], 'content|html'));
    }

    /** @return array{0: Catalog, 1: BlockRenderer} */
    private static function runtime(string $engine): array
    {
        $src = new LocalSource('ui8kit', self::SRC.'/'.$engine, $engine);

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
