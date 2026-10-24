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
use WPFastyBuilder\Plugin;
use WPFastyBuilder\Rest;
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

/** POST /render-block: one block's HTML equals the same block inside the compiled page (Latte and Twig). */
final class FragmentTest extends TestCase
{
    private const SRC = __DIR__ . '/../plugins/wpfasty-builder/sources/ui8kit';

    private const POST = ['id' => 'post-1', 'title' => 'A "real" post', 'excerpt' => 'E', 'category' => 'News',
        'author' => 'Ann', 'date' => 'Oct 1, 2026', 'minutes' => 2, 'icon' => 'file-text',
        'href' => 'https://example.test/a/', 'image' => 'https://example.test/a.jpg'];

    /** @return array{0: Catalog, 1: BlockRenderer} */
    private static function runtime(string $engine): array
    {
        $src = new LocalSource('ui8kit', self::SRC . '/' . $engine, $engine);
        return [new Catalog($src->catalog()), new BlockRenderer(['ui8kit' => $src], null)];
    }

    /** @return list<array<string, mixed>> */
    private static function layoutBlocks(): array
    {
        return [
            ['id' => 'intro', 'block' => 'pagehead', 'variant' => 'start', 'props' => ['title' => "It's <b>Builder</b> \\ page"]],
            ['id' => 'news', 'block' => 'posts', 'variant' => 'grid', 'bindings' => ['posts' => ['source' => 'posts', 'limit' => 3]]],
            ['id' => 'about', 'block' => 'about', 'variant' => 'split', 'props' => ['stats' => [['id' => 's', 'label' => 'L', 'value' => '1', 'icon' => 'zap', 'hint' => 'h']]]],
            ['id' => 'cta', 'block' => 'cta', 'variant' => 'card', 'props' => ['content' => ['title' => 'Go & see']]],
        ];
    }

    /** @return array<string, string> block id => fragment of the compiled page, rendered with bound posts */
    private static function page(string $engine, Catalog $catalog, BlockRenderer $r, array $entry): array
    {
        $tpl = Compiler::compile($entry, $catalog, $engine);
        $ctx = ['wpfb' => ['news' => ['posts' => [self::POST]]]];
        $fn = static fn (string $s, string $t, array $p): string => $r->render($s, $t, $p);
        if ($engine === 'latte') {
            $l = new Engine();
            $l->setLoader(new StringLoader(['page' => $tpl]));
            $l->addFunction('wpfb_block', $fn);
            $html = $l->renderToString('page', $ctx);
        } else {
            $env = new \Twig\Environment(new \Twig\Loader\ArrayLoader(['page' => $tpl]), ['autoescape' => 'html']);
            $env->registerUndefinedFunctionCallback(static fn (string $n): \Twig\TwigFunction|false
                => $n === 'wpfb_block' ? new \Twig\TwigFunction($n, $fn, ['is_safe' => ['html']]) : false);
            $html = $env->render('page', $ctx);
        }
        return (require dirname(__DIR__) . '/tools/builder/fragment-html.php')['blocks']('<!doctype html><html><body>' . $html . '</body></html>');
    }

    public function testFragmentEqualsTheBlockInTheCompiledPage(): void
    {
        $html = require dirname(__DIR__) . '/tools/builder/fragment-html.php';
        foreach (['latte', 'twig'] as $engine) {
            [$catalog, $r] = self::runtime($engine);
            [$entry] = Layout::normalize(['blocks' => self::layoutBlocks()], 'ui8kit', $catalog, ['posts'], [
                'id' => 'layout-7', 'slug' => 'home', 'title' => 'Home', 'version' => 1,
                'created_at' => '2026-10-17T01:00:00Z', 'updated_at' => '2026-10-17T01:00:00Z',
            ]);
            $page = self::page($engine, $catalog, $r, $entry);
            $this->assertCount(4, $page);
            foreach (self::layoutBlocks() as $b) {
                $seen = [];
                $out = Fragment::render(
                    $catalog,
                    'ui8kit',
                    $b,
                    ['posts'],
                    static function (array $bind, string $kind) use (&$seen): array {
                        $seen[] = $bind['source'] . ':' . $kind . ':' . $bind['limit'];
                        return [self::POST];
                    },
                    static fn (string $t, array $p): string => $r->render('ui8kit', $t, $p),
                );
                $this->assertNotNull($out);
                $this->assertSame($b['id'], $out['id']);
                $this->assertStringNotContainsString('<!-- wpfasty-builder', $out['html']);
                $this->assertSame($page[$b['id']], $html['inner']($out['html']), "$engine {$b['id']}");
                $this->assertSame($b['id'] === 'news' ? ['posts:post:3'] : [], $seen);
            }
        }
    }

    public function testFragmentSanitizesLikeASavedLayout(): void
    {
        [$catalog, $r] = self::runtime('latte');
        $out = Fragment::render($catalog, 'ui8kit', [
            'id' => 'bad id!', 'block' => 'pagehead', 'variant' => 'start',
            'props' => ['title' => '<script>alert(1)</script>Hi', 'lead' => str_repeat('x', 3000)],
            'bindings' => ['nope' => ['source' => 'posts'], 'posts' => ['source' => 'evil']],
        ], ['posts'], static fn (): array => [], static fn (string $t, array $p): string => json_encode($p) ?: '');
        $this->assertNotNull($out);
        $this->assertSame('b1', $out['id']);
        $props = json_decode($out['html'], true);
        $this->assertSame('alert(1)Hi', $props['title']);
        $this->assertSame(2000, strlen($props['lead']));
        $this->assertSame(['layout.binding_path_unknown', 'layout.binding_path_unknown'], array_column($out['issues'], 'code'));
        // unknown block, unknown variant: nothing renders
        $none = static fn (): string => 'x';
        $this->assertNull(Fragment::render($catalog, 'ui8kit', ['block' => 'nope', 'variant' => 'x'], [], static fn (): array => [], $none));
        $this->assertNull(Fragment::render($catalog, 'ui8kit', ['block' => 'pagehead', 'variant' => 'x'], [], static fn (): array => [], $none));
    }

    public function testFragmentRendersOnlyTheCatalogRuntime(): void
    {
        [$latte] = self::runtime('latte');
        $twigNamed = json_decode((string) json_encode((new LocalSource('ui8kit', self::SRC . '/latte', 'latte'))->catalog()), true);
        $twigNamed['runtime'] = $twigNamed['templates'] = 'twig';
        // a Twig catalog whose variant still names a .latte template is not rendered by a Twig site
        $out = Fragment::render(new Catalog($twigNamed), 'ui8kit', ['block' => 'pagehead', 'variant' => 'start'], [], static fn (): array => [], static fn (): string => 'x');
        $this->assertNull($out);
        $this->assertNotNull(Fragment::render($latte, 'ui8kit', ['block' => 'pagehead', 'variant' => 'start'], [], static fn (): array => [], static fn (): string => 'x'));
    }

    public function testRestRenderBlock(): void
    {
        Plugin::$dir = dirname(__DIR__) . '/plugins/wpfasty-builder';
        add_filter('wpfasty_builder_engine', static fn (): string => 'latte');
        $GLOBALS['wp_test_routes'] = [];
        Rest::routes();
        $route = $GLOBALS['wp_test_routes'][Rest::NS . '/render-block'];
        $this->assertSame('POST', $route['methods']);
        $perm = $route['permission_callback'];
        $GLOBALS['wp_test_caps'] = [];
        $this->assertFalse($perm(new \WP_REST_Request([])));
        $GLOBALS['wp_test_caps'] = ['edit_pages'];
        $this->assertTrue($perm(new \WP_REST_Request([])));
        $this->assertFalse($perm(new \WP_REST_Request(['page' => 7])), 'with a page: edit_page of that page');
        $GLOBALS['wp_test_caps'] = ['edit_page:7'];
        $this->assertTrue($perm(new \WP_REST_Request(['page' => 7])));

        $res = Rest::renderBlock(new \WP_REST_Request([
            'id' => 'intro', 'block' => 'pagehead', 'variant' => 'start', 'props' => ['title' => 'Live <i>edit</i>'],
        ]));
        $this->assertInstanceOf(\WP_REST_Response::class, $res);
        $data = $res->get_data();
        $this->assertSame('intro', $data['id']);
        $this->assertStringContainsString('Live edit', $data['html']);
        $this->assertStringNotContainsString('<i>', $data['html']);
        [$catalog, $r] = self::runtime('latte');
        $direct = $r->render('ui8kit', 'blocks/page-intro.latte', Compiler::blockProps(
            (array) $catalog->variant('pagehead', 'start'),
            ['props' => ['title' => 'Live edit'], 'bindings' => []],
        ));
        $this->assertSame($direct, $data['html']);

        $bad = Rest::renderBlock(new \WP_REST_Request(['block' => 'nope', 'variant' => 'x']));
        $this->assertInstanceOf(\WP_Error::class, $bad);
        $this->assertSame(400, $bad->get_error_data()['status']);
        $src = Rest::renderBlock(new \WP_REST_Request(['source' => 'other', 'block' => 'pagehead', 'variant' => 'start']));
        $this->assertInstanceOf(\WP_Error::class, $src);
        $this->assertSame(404, $src->get_error_data()['status']);
        unset($GLOBALS['wp_test_filters']['wpfasty_builder_engine'], $GLOBALS['wp_test_caps']);
    }
}
