<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WPFastyBuilder\Catalog;
use WPFastyBuilder\Codex\Compat;
use WPFastyBuilder\Codex\Sources;
use WPFastyBuilder\DataSources;

/** Builder sources from the Codex manifest, Compat rules R1–R9, the record plan (no WordPress needed). */
final class CodexCompatTest extends TestCase
{
    /** @return array<string, array<string, mixed>> */
    private static function sources(string $site): array
    {
        $t = dirname(__DIR__) . "/themes/latte-$site/types";
        return Sources::fromManifest(
            json_decode((string) file_get_contents("$t/codex.manifest.json"), true),
            json_decode((string) file_get_contents("$t/codex.delivery.json"), true),
        );
    }

    public function testSourcesComeFromTheManifest(): void
    {
        $shop = self::sources('shop');
        $this->assertSame(['pages', 'posts', 'products', 'categories', 'post_tags', 'product_cats'], array_keys($shop));
        $this->assertSame('stock', $shop['products']['stock']);
        $this->assertSame(['number', ['money']], $shop['products']['fields']['price']);
        $this->assertSame('category', $shop['product_cats']['role']);
        $this->assertArrayNotHasKey('shop_orders', $shop, 'not REST-exposed: not a Codex kind');
        $folio = self::sources('folio');
        $this->assertSame(['number', ['integer']], $folio['projects']['fields']['year']);
        $this->assertSame(['boolean', []], $folio['projects']['fields']['featured']);
    }

    public function testRules(): void
    {
        $s = self::sources('shop');
        $p = $s['products'];
        $r = static fn (string $path, array $src, ?array $need = null): array => Compat::resolve($path, $src, $need);
        $this->assertSame(['exact', 'metadata.price.value'], [$r('metadata.price.value', $p)['level'], $r('metadata.price.value', $p)['path']]);
        $this->assertSame('excerpt|excerpt-fallback', $r('metadata.summary.value', $p)['path'], 'R1');
        $this->assertSame('terms|term:product_cat', $r('metadata.category.value', $p)['path'], 'R2');
        $this->assertSame('terms|term:product_cat', $r('terms|term:product_category', $p)['path'], 'R2 by role');
        $this->assertSame('published_at|date', $r('metadata.date.value', $s['posts'])['path'], 'R3');
        $this->assertSame('content|reading-minutes', $r('metadata.minutes.value', $s['posts'])['path'], 'R4');
        $this->assertSame('metadata.currency.value|settings:currency', $r('metadata.currency.value', $p)['path'], 'R5');
        $this->assertSame('metadata.stock.value|in-stock', $r('metadata.inStock.value', $p)['path'], 'R6');
        $this->assertSame('terms|term-names:post_tag', $r('metadata.tags.value', $s['posts'])['path'], 'R7');
        $this->assertSame('exact', $r('metadata.count.value', $s['product_cats'])['level'], 'R8: terms carry count');
        $this->assertSame(['auto', 'name'], [$r('title', $s['product_cats'])['level'], $r('title', $s['product_cats'])['path']], 'R9');
        $this->assertSame('manual', $r('metadata.price.value', $s['posts'])['level']);
        $this->assertSame('manual', $r('metadata.price.value', $p, ['boolean', []])['level'], 'type');
        $icon = $r('metadata.icon.value', $p);
        $this->assertSame(['manual', 'shopping-bag'], [$icon['level'], $icon['default']], 'Q6 default');
        $this->assertSame('folder', $r('metadata.icon.value', $s['product_cats'])['default']);
    }

    public function testRecordPlanOfTheBundledCatalog(): void
    {
        $catalog = new Catalog(json_decode((string) file_get_contents(
            dirname(__DIR__) . '/plugins/wpfasty-builder/sources/ui8kit/latte/catalog.json',
        ), true));
        $slot = $catalog->slot('posts', 'grid', 'posts');
        $this->assertSame('post', $slot['kind']);
        $folio = self::sources('folio');
        $plan = DataSources::plan($slot, $folio['projects'], ['author' => 'metadata.client.value']);
        $this->assertSame('metadata.client.value', $plan['author'][0], 'manual map wins');
        $this->assertSame('published_at|date', $plan['date'][0]);
        $this->assertSame([null, 'briefcase'], [$plan['icon'][0], $plan['icon'][1]]);
        $this->assertSame('slug|route', $plan['href'][0], 'the catalog maps href itself');
        $this->assertSame('featured_media_id|media', $plan['image'][0]);
        $this->assertSame('auto', DataSources::level($slot, $folio['posts']));
        $this->assertSame('manual', DataSources::level((array) $catalog->slot('products', 'grid3', 'products'), $folio['posts']));
        $products = (array) $catalog->slot('products', 'grid3', 'products');
        $this->assertSame('auto', DataSources::level($products, self::sources('shop')['products']), 'R1/R2/R5/R6');
    }
}
