<?php

/**
 * Money helpers — integer cents internally, formatted for display.
 *
 * @package Latte_Shop
 */

declare(strict_types=1);

namespace LatteShop;

final class Money
{
    public static function toCents(string $amount): int
    {
        $amount = trim(str_replace(',', '.', $amount));
        return $amount === '' || !is_numeric($amount) ? 0 : (int) round(((float) $amount) * 100);
    }

    public static function format(int $cents): string
    {
        $symbol = (string) apply_filters('latte_shop_currency_symbol', '$');
        $value = function_exists('number_format_i18n') ? number_format_i18n($cents / 100, 2) : number_format($cents / 100, 2);
        return $symbol . $value;
    }
}
