<?php

declare(strict_types=1);

namespace WPFasty\Tests;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Links;
use WPFastyBuilder\Records;

/**
 * Record tokens (Records): {post:<slug>}, {product:<slug>}, {term:<taxonomy>:<slug>}, {price:<product>},
 * {stock:<product>} — one query per type for the whole page, platform-neutral product data (money in minor units,
 * currency from the settings, the in-stock pipe), safe fallbacks.
 */
#[RunTestsInSeparateProcesses]
final class RecordsTest extends TestCase
{
    /** the purchasable kind of the shop manifest (delivery meta + manifest fields) */
    private const PRODUCT = [
        'kind' => 'product', 'post_type' => 'product',
        'meta' => ['price' => '_shop_price', 'sale_price' => '_shop_sale_price', 'stock' => '_shop_stock'],
        'fields' => [
            'price' => ['type' => 'number', 'rules' => [['name' => 'fastygo.codex/money']]],
            'sale_price' => ['type' => 'number', 'rules' => [['name' => 'fastygo.codex/money']]],
            'stock' => ['type' => 'number', 'rules' => [['name' => 'fastygo.codex/integer']]],
        ],
        'price' => 'price', 'sale' => 'sale_price', 'stock' => 'stock',
    ];

    protected function setUp(): void
    {
        require_once __DIR__ . '/fixtures/records-stubs.php';
        require_once __DIR__ . '/fixtures/wp-objects-stubs.php';
        require_once __DIR__ . '/fixtures/links-stubs.php';
        $src = dirname(__DIR__) . '/plugins/wpfasty-builder/src/';
        foreach (['Links', 'Records', 'Layout', 'Codex/ReadPath', 'Codex/Format'] as $f) {
            require_once $src . $f . '.php';
        }
        require_once dirname(__DIR__) . '/plugins/wpfasty-types/src/Codex/Mapper.php';
        $post = static fn (int $id, string $type, string $slug, string $title): \WP_Post
            => new \WP_Post(['ID' => $id, 'post_type' => $type, 'post_name' => $slug, 'post_title' => $title]);
        $GLOBALS['wpfb_records'] = [
            'posts' => [
                $post(1, 'post', 'hello-world', 'Hello world'),
                $post(2, 'post', 'second', 'Second'),
                $post(10, 'product', 'mug', 'Ceramic mug'),
                $post(11, 'product', 'wallet', 'Leather wallet'),
                $post(12, 'product', 'tray', 'Oak tray'),
                $post(13, 'product', 'gift', 'Gift card'),
            ],
            'meta' => [
                10 => ['_shop_price' => '1800'],
                11 => ['_shop_price' => '4800', '_shop_sale_price' => '3900', '_shop_stock' => '20'],
                12 => ['_shop_price' => '6450', '_shop_stock' => '0'],
            ],
            'terms' => [
                new \WP_Term(['term_id' => 5, 'name' => 'Kitchen', 'slug' => 'kitchen', 'taxonomy' => 'product_cat']),
                new \WP_Term(['term_id' => 6, 'name' => 'Sale', 'slug' => 'sale', 'taxonomy' => 'post_tag']),
            ],
            'log' => [],
        ];
        $GLOBALS['wp_test_permalinks'] = [
            1 => 'http://example.test/hello-world/', 2 => 'http://example.test/second/',
            10 => 'http://example.test/product/mug/', 11 => 'http://example.test/product/wallet/',
            12 => 'http://example.test/product/tray/', 13 => 'http://example.test/product/gift/',
        ];
        Records::setSite([
            'product' => self::PRODUCT, 'taxonomies' => ['category', 'post_tag', 'product_cat'],
            'currency' => 'USD', 'locale' => 'en-US',
        ]);
    }

    /** @return list<string> functions of the queries made so far */
    private static function queries(): array
    {
        return array_column($GLOBALS['wpfb_records']['log'], 0);
    }

    public function testPageTokensLoadInOneQueryPerType(): void
    {
        $props = [
            ['title' => 'Read {post:hello-world} and {post:second}', 'href' => '{product:mug}'],
            ['items' => [
                ['href' => '{product:wallet}#buy', 'price' => '{price:wallet}', 'stock' => '{stock:tray}'],
                ['href' => '{term:product_cat:kitchen}', 'tag' => '{term:post_tag:sale}', 'x' => '{price:missing}'],
            ]],
        ];
        Links::prime($props);
        $this->assertSame(['get_posts', 'get_posts', 'get_terms'], self::queries());
        $log = $GLOBALS['wpfb_records']['log'];
        $this->assertSame(['hello-world', 'second'], $log[0][1]['post_name__in']);
        $this->assertSame([false, false], [$log[0][1]['update_post_meta_cache'], $log[0][1]['update_post_term_cache']]);
        $this->assertSame(['mug', 'wallet', 'tray', 'missing'], $log[1][1]['post_name__in']);
        $this->assertSame([true, false], [$log[1][1]['update_post_meta_cache'], $log[1][1]['update_post_term_cache']]);
        $this->assertSame([['product_cat', 'post_tag'], ['kitchen', 'sale']], [$log[2][1]['taxonomy'], $log[2][1]['slug']]);
        $this->assertSame([
            ['title' => 'Read http://example.test/hello-world/ and http://example.test/second/', 'href' => 'http://example.test/product/mug/'],
            ['items' => [
                ['href' => 'http://example.test/product/wallet/#buy', 'price' => '$39', 'stock' => 'Out of stock'],
                ['href' => 'http://example.test/product_cat/kitchen/', 'tag' => 'http://example.test/post_tag/sale/', 'x' => ''],
            ]],
        ], Links::walk($props));
        // everything was primed: rendering made no query
        $this->assertCount(3, self::queries());
        // priming the same page again (another block render) loads nothing
        Links::prime($props);
        $this->assertCount(3, self::queries());
    }

    public function testProductDataIsPlatformNeutral(): void
    {
        $p = Records::product($GLOBALS['wpfb_records']['posts'][3]);
        // effective price (the lower sale price) in minor units, currency from the settings, in stock
        $this->assertSame([
            'url' => 'http://example.test/product/wallet/', 'label' => 'Leather wallet', 'amount' => 3900,
            'currency' => 'USD', 'price' => '$39', 'in_stock' => true, 'stock' => 'In stock',
        ], $p);
        $this->assertSame('$64.50', Records::value('price', 'tray'));
        $this->assertSame('Out of stock', Records::value('stock', 'tray'));
        // no stock value = unlimited (in-stock pipe); no price: "" (nothing to format)
        $this->assertSame('In stock', Records::value('stock', 'gift'));
        $this->assertSame('', Records::value('price', 'gift'));
        $this->assertSame('$18', Links::resolve('{price:mug}'));
        foreach (array_keys($p) as $k) {
            $this->assertStringStartsNotWith('wp_', $k);
            $this->assertStringStartsNotWith('post_', $k);
        }
    }

    public function testUnknownRecordsDegradeSafely(): void
    {
        // an unknown taxonomy is never queried
        $this->assertSame('#', Links::resolve('{term:nope:kitchen}'));
        $this->assertNotContains('get_terms', self::queries());
        $this->assertSame('#|#|#|#||', Links::resolve('{post:nope}|{product:nope}|{term:product_cat:nope}|{term:nope:kitchen}|{price:nope}|{stock:nope}'));
        // {term:<tax>} without a slug, a second argument where none is taken: not a link
        $this->assertSame('#', Links::resolve('{term:product_cat}'));
        $this->assertSame('{post:a:b}', Links::resolve('{post:a:b}'));
    }

    public function testWithoutAPurchasableKindProductsAreEmpty(): void
    {
        Records::setSite(['taxonomies' => []]);
        $this->assertSame('#|#||', Links::resolve('{product:mug}|{term:product_cat:kitchen}|{price:mug}|{stock:mug}'));
        $this->assertSame([], self::queries());
        $index = Records::index();
        $this->assertNull($index['products']);
        $this->assertSame([], $index['terms']);
        $this->assertSame(['get_posts'], self::queries());
    }

    public function testIndexListsPostsProductsAndTermsInOneQueryPerList(): void
    {
        $index = Records::index();
        $this->assertSame(['get_posts', 'get_posts', 'get_terms'], self::queries());
        $this->assertSame([
            ['token' => '{post:hello-world}', 'label' => 'Hello world', 'slug' => 'hello-world', 'url' => 'http://example.test/hello-world/'],
            ['token' => '{post:second}', 'label' => 'Second', 'slug' => 'second', 'url' => 'http://example.test/second/'],
        ], $index['posts']);
        $this->assertSame(
            ['{product:mug}' => '$18', '{product:wallet}' => '$39', '{product:tray}' => '$64.50', '{product:gift}' => ''],
            array_column($index['products'], 'price', 'token'),
        );
        $this->assertSame([1800, 3900, 6450, null], array_column($index['products'], 'amount'));
        // category terms are listed by {category:<slug>} already
        $this->assertSame(['post_tag', 'product_cat'], $GLOBALS['wpfb_records']['log'][2][1]['taxonomy']);
        $this->assertSame(['{term:product_cat:kitchen}', '{term:post_tag:sale}'], array_column($index['terms'], 'token'));
        // what the picker shows is what the tokens give on the site, without another query
        foreach ([...$index['posts'], ...$index['products'], ...$index['terms']] as $t) {
            $this->assertSame($t['url'], Links::resolve($t['token']), $t['token']);
        }
        foreach ($index['products'] as $p) {
            $this->assertSame($p['price'], Links::resolve('{price:' . $p['slug'] . '}'));
            $this->assertSame($p['stock'], Links::resolve('{stock:' . $p['slug'] . '}'));
        }
        $this->assertCount(3, self::queries());
    }

    public function testPriceAndStockAreNeverLinks(): void
    {
        $this->assertTrue(Layout::safeHref('{product:mug}'));
        $this->assertTrue(Layout::safeHref('{term:product_cat:kitchen}'));
        $this->assertFalse(Layout::safeHref('{price:mug}'));
        $this->assertFalse(Layout::safeHref('{stock:mug}'));
    }
}
