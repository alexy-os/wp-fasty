<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Links;
use WPFastyBuilder\Rest;

/**
 * GET wpfasty-builder/v1/links (Links::index): the editor's link picker data — site tokens, published top-level
 * pages, categories and post type archives with the URL each token resolves to; one fixed set of queries.
 */
#[RunTestsInSeparateProcesses]
final class LinksRestTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/fixtures/wp-objects-stubs.php';
        require_once __DIR__ . '/fixtures/links-stubs.php';
        require_once __DIR__ . '/fixtures/links-index-stubs.php';
        require_once __DIR__ . '/fixtures/rest-stubs.php';
        require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Links.php';
        require_once dirname(__DIR__) . '/plugins/wpfasty-builder/src/Rest.php';
        $GLOBALS['wpfb_links'] = [
            'pages' => [
                new \WP_Post(['ID' => 11, 'post_name' => 'about', 'post_title' => 'About <b>us</b>', 'post_type' => 'page']),
                new \WP_Post(['ID' => 12, 'post_name' => 'contact', 'post_title' => '', 'post_type' => 'page']),
                // not addressable by {page:<slug>}: skipped
                new \WP_Post(['ID' => 13, 'post_name' => '%d0%be-%d0%bd%d0%b0%d1%81', 'post_title' => 'О нас', 'post_type' => 'page']),
            ],
            'terms' => [new \WP_Term(['term_id' => 5, 'name' => 'News', 'slug' => 'news'])],
            'types' => ['product' => 'Products'],
            'posts_page' => 0,
            'calls' => [],
            'args' => [],
        ];
    }

    public function testRouteNeedsEditPages(): void
    {
        $GLOBALS['wp_test_routes'] = [];
        Rest::routes();
        $route = $GLOBALS['wp_test_routes'][Rest::NS . '/links'];
        $this->assertSame('GET', $route['methods']);
        $GLOBALS['wp_test_caps'] = [];
        $this->assertFalse(($route['permission_callback'])());
        $GLOBALS['wp_test_caps'] = ['edit_pages'];
        $this->assertTrue(($route['permission_callback'])());
    }

    public function testIndexListsEveryTargetWithItsUrl(): void
    {
        $data = Rest::links()->get_data();
        $this->assertSame('http://example.test/', $data['home']);
        $this->assertSame([
            ['token' => '{home}', 'label' => 'Home', 'url' => 'http://example.test/'],
            ['token' => '{posts}', 'label' => 'Posts', 'url' => 'http://example.test/'],
        ], $data['site']);
        $this->assertSame([
            ['token' => '{page:about}', 'label' => 'About us', 'slug' => 'about', 'url' => 'http://example.test/about/'],
            ['token' => '{page:contact}', 'label' => 'contact', 'slug' => 'contact', 'url' => 'http://example.test/contact/'],
        ], $data['pages']);
        $this->assertSame([
            ['token' => '{category:news}', 'label' => 'News', 'slug' => 'news', 'kind' => 'category', 'url' => 'http://example.test/category/news/'],
            ['token' => '{archive:product}', 'label' => 'Products', 'slug' => 'product', 'kind' => 'archive', 'url' => 'http://example.test/product/'],
        ], $data['sections']);
        // the listed tokens resolve server-side to the URL the editor shows (Links::resolve; the category lookup of
        // links-stubs.php finds nothing, so only site and archive tokens are compared here)
        foreach ([...$data['site'], $data['sections'][1]] as $t) {
            $this->assertSame($t['url'], Links::resolve($t['token']), $t['token']);
        }
    }

    public function testOneQueryPerList(): void
    {
        $data = Links::index();
        $calls = $GLOBALS['wpfb_links']['calls'];
        // one call per list, whatever the number of items (no per-item queries: links come from the loaded objects)
        $this->assertSame(['get_posts' => 1, 'get_terms' => 1, 'get_post_types' => 1], $calls);
        $args = $GLOBALS['wpfb_links']['args']['get_posts'];
        $this->assertSame(['page', 'publish', 0, true, false, false], [
            $args['post_type'], $args['post_status'], $args['post_parent'], $args['no_found_rows'],
            $args['update_post_meta_cache'], $args['update_post_term_cache'],
        ]);
        $this->assertSame([['public' => true, 'has_archive' => true], 'objects'], $GLOBALS['wpfb_links']['args']['get_post_types']);
        $this->assertSame(Links::MAX, $args['numberposts']);
        $this->assertIsArray($data['pages']);
    }
}
