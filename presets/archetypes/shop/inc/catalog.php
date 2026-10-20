<?php

/**
 * Catalog: pricing/stock rules for any post type with the `purchasable` capability.
 *
 * @package Latte_Shop
 */

declare(strict_types=1);

namespace LatteShop;

final class Catalog
{
    /**
     * Product view-model (module `context` of types/product.php).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function viewModel(array $data, \WP_Post $post): array
    {
        $price = self::price($post->ID);
        $regular = self::regularPrice($post->ID);
        $stock = self::stock($post->ID);
        $data['product'] = [
            'price' => Money::format($price),
            'regular' => $regular > $price ? Money::format($regular) : '',
            'on_sale' => $regular > $price,
            'sku' => (string) ($data['fields']['sku'] ?? ''),
            'stock' => $stock,
            'in_stock' => $stock === null || $stock > 0,
            'purchasable' => self::isPurchasable($post->ID),
        ];
        $data['product_cats'] = $data['terms']['product_cat'] ?? [];
        return $data;
    }

    /** Field name of a purchasable capability option (price_field, sale_field, stock_field). */
    private static function fieldValue(int $id, string $option): string
    {
        $type = (string) get_post_type($id);
        $module = \WPFasty\Core\Application::getInstance()->get('types.registry')->get($type);
        $cap = $module?->capability('purchasable') ?? [];
        $field = (string) ($cap[$option] ?? '');
        return $field === '' ? '' : wpfasty_field($id, $field);
    }

    public static function isPurchasable(int $id): bool
    {
        $p = get_post($id);
        return $p instanceof \WP_Post
            && wpfasty_type_supports($p->post_type, 'purchasable')
            && $p->post_status === 'publish'
            && self::price($id) > 0
            && (self::stock($id) === null || self::stock($id) > 0);
    }

    /** Effective unit price in cents (sale wins when lower). */
    public static function price(int $id): int
    {
        $regular = Money::cents(self::fieldValue($id, 'price_field'));
        $sale = Money::cents(self::fieldValue($id, 'sale_field'));
        return $sale > 0 && $sale < $regular ? $sale : $regular;
    }

    public static function regularPrice(int $id): int
    {
        return Money::cents(self::fieldValue($id, 'price_field'));
    }

    public static function stock(int $id): ?int
    {
        $raw = self::fieldValue($id, 'stock_field');
        return $raw === '' ? null : max(0, (int) $raw);
    }

    public static function setStock(int $id, int $stock): void
    {
        $type = \WPFasty\Core\Application::getInstance()->get('types.registry')->get((string) get_post_type($id));
        $field = (string) ($type?->capability('purchasable')['stock_field'] ?? '');
        if ($field !== '' && isset($type->fields[$field])) {
            update_post_meta($id, $type->fields[$field]->metaKey, max(0, $stock));
        }
    }
}
