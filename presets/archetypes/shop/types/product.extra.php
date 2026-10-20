<?php
/**
 * Archetype fragment: callables (admin columns + view model) appended to the generated types/product.php by theme:generate.
 * Only the entries between `return [` and `];` are used; {{theme.textDomain}} is substituted.
 *
 * @package Latte
 */

declare(strict_types=1);

return [
    'columns' => [
        'price' => [
            'label' => __('Price', '{{theme.textDomain}}'),
            'value' => static fn (int $id): string => LatteShop\Money::format(LatteShop\Catalog::price($id)),
        ],
        'stock' => ['label' => __('Stock', '{{theme.textDomain}}'), 'value' => static function (int $id): string {
            $stock = LatteShop\Catalog::stock($id);
            return $stock === null ? '∞' : (string) $stock;
        }],
    ],
    'context' => [LatteShop\Catalog::class, 'viewModel'],
];
