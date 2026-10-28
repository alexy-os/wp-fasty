<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Admin;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Pages;
use WPFastyBuilder\Plugin;
use WPFastyBuilder\Rest;
use WPFastyBuilder\Source\LocalSource;
use WPFastyBuilder\Starters;

/**
 * Pages from the Builder: POST wpfasty-builder/v1/pages (Pages::create) makes a draft page, a starter set puts the
 * blocks of a demo landing into its Builder draft and never sets the page template; the starters are the landings of
 * tools/builder/landings and are offered only when they fit the site; "Edit with Builder" row action and
 * "Add New with Builder" on the Pages list.
 */
#[RunTestsInSeparateProcesses]
final class PagesTest extends TestCase
{
    private string $plugin;

    protected function setUp(): void
    {
        $this->plugin = dirname(__DIR__) . '/plugins/wpfasty-builder';
        $src = $this->plugin . '/src/';
        spl_autoload_register(static function (string $class) use ($src): void {
            if (str_starts_with($class, 'WPFastyBuilder\\')) {
                $file = $src . str_replace('\\', '/', substr($class, 15)) . '.php';
                if (is_file($file)) {
                    require_once $file;
                }
            }
        });
        require_once __DIR__ . '/fixtures/rest-stubs.php';
        require_once __DIR__ . '/fixtures/pages-stubs.php';
        Plugin::$file = $this->plugin . '/wpfasty-builder.php';
        Plugin::$dir = $this->plugin;
        $GLOBALS['wp_test_filters']['wpfasty_builder_engine'] = [static fn (): string => 'latte'];
        $GLOBALS['wp_test_filters']['wpfasty_builder_data_sources'] = [
            static fn (): array => ['posts' => [], 'products' => [], 'projects' => []],
        ];
        $GLOBALS['wpfb_pages'] = ['inserted' => [], 'deleted' => [], 'taken' => ['about']];
    }

    public function testStartersAreTheDemoLandings(): void
    {
        $shipped = Starters::shipped();
        $this->assertSame(['shop', 'folio', 'press'], array_column($shipped, 'id'));
        foreach ($shipped as $s) {
            $landing = json_decode((string) file_get_contents(dirname(__DIR__) . '/tools/builder/landings/' . $s['id'] . '.json'), true);
            $this->assertSame($landing['layout'], $s['layout'], $s['id'] . ': starters/<id>.json must keep the layout of its landing');
            $this->assertNotSame('', $s['label']);
            $this->assertArrayNotHasKey('page', $s, 'a starter is only blocks: no page title, slug or template');
        }
    }

    public function testStartersFitTheBundledCatalogsAndNormalizeCleanly(): void
    {
        foreach (['latte', 'twig'] as $runtime) {
            $catalog = new Catalog((new LocalSource('ui8kit', $this->plugin . '/sources/ui8kit/' . $runtime, $runtime))->catalog());
            foreach (Starters::shipped() as $s) {
                $this->assertTrue(Starters::fits($s, $catalog, ['posts', 'products', 'projects']), $runtime . ' ' . $s['id']);
                [$entry, $issues] = Layout::normalize($s['layout'], 'ui8kit', $catalog, ['posts', 'products', 'projects'], [
                    'id' => 'layout-1', 'locale' => 'en', 'slug' => 'x', 'title' => 'X', 'status' => 'draft', 'version' => 1,
                    'created_at' => '2026-10-28T00:00:00Z', 'updated_at' => '2026-10-28T00:00:00Z', 'page_id' => 1,
                ]);
                $this->assertSame([], $issues, $runtime . ' ' . $s['id']);
                $this->assertCount(count($s['layout']['blocks']), Layout::blocks($entry));
            }
        }
    }

    public function testOnlyStartersThatFitTheSiteAreOffered(): void
    {
        $this->assertSame(['shop', 'folio', 'press'], array_column(Starters::forEditor(), 'id'));
        $this->assertSame(4, Starters::forEditor()[0]['blocks']);
        $this->assertArrayNotHasKey('layout', Starters::forEditor()[0]);
        $catalog = new Catalog((new LocalSource('ui8kit', $this->plugin . '/sources/ui8kit/latte', 'latte'))->catalog());
        [$shop] = Starters::shipped();
        // no products on this site (theme without the product type): the shop starter is not offered
        $this->assertFalse(Starters::fits($shop, $catalog, ['posts', 'projects']));
        $broken = $shop;
        $broken['layout']['blocks'][0]['variant'] = 'no-such-variant';
        $this->assertFalse(Starters::fits($broken, $catalog, ['posts', 'products', 'projects']));
        $this->assertFalse(Starters::fits($shop, null, ['posts', 'products', 'projects']), 'source not installed');
    }

    public function testRouteNeedsEditPages(): void
    {
        $GLOBALS['wp_test_routes'] = [];
        Rest::routes();
        $route = $GLOBALS['wp_test_routes'][Rest::NS . '/pages'];
        $this->assertSame('POST', $route['methods']);
        $this->assertTrue($route['args']['title']['required']);
        $GLOBALS['wp_test_caps'] = [];
        $this->assertFalse(($route['permission_callback'])());
        $GLOBALS['wp_test_caps'] = ['edit_pages'];
        $this->assertTrue(($route['permission_callback'])());
    }

    public function testEmptyCreatesADraftPageWithoutBlocksOrTemplate(): void
    {
        $saved = [];
        $out = Pages::create('  Summer <b>Sale</b> ', '', 'empty', static function () use (&$saved): array {
            $saved[] = func_get_args();
            return [[], []];
        });
        $this->assertIsArray($out);
        $this->assertSame([], $saved, 'empty: no Builder draft');
        $this->assertSame(['id' => 101, 'slug' => 'summer-sale', 'blocks' => 0], ['id' => $out['id'], 'slug' => $out['slug'], 'blocks' => $out['blocks']]);
        $this->assertSame('http://example.test/wp-admin/edit.php?post_type=page&page=wpfasty-builder&post=101', $out['url']);
        $this->assertSame([[
            'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Summer Sale', 'post_name' => 'summer-sale', 'post_content' => '',
        ]], $GLOBALS['wpfb_pages']['inserted'], 'no page_template, no meta: the page template is not touched');
    }

    public function testSlugIsSanitizedAndMadeUnique(): void
    {
        $out = Pages::create('About', 'About', 'empty');
        $this->assertSame('about-2', $out['slug']);
        $out = Pages::create('Contacts', 'Our Contacts!', 'empty');
        $this->assertSame('our-contacts', $out['slug']);
    }

    public function testStarterPutsItsBlocksIntoTheBuilderDraft(): void
    {
        $calls = [];
        $out = Pages::create('Shop home', 'shop-home', 'shop', static function (int $id, array $layout, bool $publish) use (&$calls): array {
            $calls[] = [$id, $layout, $publish];
            return [['metadata' => ['blocks' => ['value' => $layout['blocks']]]], []];
        });
        $this->assertIsArray($out);
        $this->assertSame(4, $out['blocks']);
        [$shop] = Starters::shipped();
        $this->assertSame([[101, $shop['layout'], false]], $calls, 'the draft, not published');
        $this->assertArrayNotHasKey('page_template', $GLOBALS['wpfb_pages']['inserted'][0]);
    }

    public function testErrors(): void
    {
        $e = Pages::create('   ', 'x', 'empty');
        $this->assertInstanceOf(\WP_Error::class, $e);
        $this->assertSame(['wpfb_title', 400], [$e->get_error_code(), $e->get_error_data()['status']]);
        $e = Pages::create('X', '', 'nope');
        $this->assertSame(['wpfb_starter', 400], [$e->get_error_code(), $e->get_error_data()['status']]);
        $this->assertSame([], $GLOBALS['wpfb_pages']['inserted'], 'nothing created on a bad request');
        // the layout cannot be saved: the new page is removed again
        $e = Pages::create('X', '', 'press', static fn (): \WP_Error => new \WP_Error('wpfb_invalid', 'bad', ['status' => 400]));
        $this->assertSame('wpfb_invalid', $e->get_error_code());
        $this->assertSame([101], $GLOBALS['wpfb_pages']['deleted']);
    }

    public function testRestResponseIs201(): void
    {
        $res = Rest::createPage(new \WP_REST_Request(['title' => 'Landing', 'starter' => 'empty']));
        $this->assertSame(201, $res->status);
        $this->assertSame('landing', $res->get_data()['slug']);
        $err = Rest::createPage(new \WP_REST_Request(['title' => '']));
        $this->assertInstanceOf(\WP_Error::class, $err);
    }

    public function testRowActionEditWithBuilder(): void
    {
        $post = new \WP_Post(['ID' => 7, 'post_type' => 'page']);
        $GLOBALS['wp_test_caps'] = ['edit_page:7'];
        $actions = Admin::rowAction(['edit' => '<a>Edit</a>'], $post);
        $this->assertSame(
            '<a href="http://example.test/wp-admin/edit.php?post_type=page&#038;page=wpfasty-builder&#038;post=7">Edit with Builder</a>',
            $actions['wpfb'],
        );
        $GLOBALS['wp_test_caps'] = ['edit_page:8'];
        $this->assertSame(['edit' => '<a>Edit</a>'], Admin::rowAction(['edit' => '<a>Edit</a>'], $post), 'no edit_page: no action');
    }

    public function testAddNewWithBuilderButton(): void
    {
        $js = Admin::addNewScript();
        $this->assertStringContainsString('document.querySelector(".wrap .page-title-action")', $js);
        $this->assertStringContainsString('"http:\/\/example.test\/wp-admin\/edit.php?post_type=page&page=wpfasty-builder&new=1"', $js);
        $this->assertStringContainsString('"Add New with Builder"', $js);
        $this->assertStringContainsString('a.after(b)', $js);
        $this->assertStringContainsString('aria-haspopup="dialog"', Admin::newPageButton());
        $this->assertStringContainsString('>New page</button>', Admin::newPageButton());
    }
}
