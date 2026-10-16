<?php

/**
 * Money helpers — integer minor units (cents) everywhere: product meta, cart, orders; formatted for display.
 * `toCents()` converts a decimal typed by a person (12.50) and is only for form input.
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

    /** Store currency, ISO 4217 (option `wpfasty_currency`, registered by the wpfasty-types plugin; default USD). */
    public static function currency(): string
    {
        $code = strtoupper((string) (function_exists('get_option') ? get_option('wpfasty_currency', 'USD') : 'USD'));
        return preg_match('/^[A-Z]{3}$/', $code) === 1 ? $code : 'USD';
    }

    /** Stored money value (integer minor units, possibly a numeric string from post meta) → int cents. */
    public static function cents(mixed $stored): int
    {
        return is_numeric($stored) ? max(0, (int) round((float) $stored)) : 0;
    }

    public static function format(int $cents): string
    {
        $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'RUB' => '₽', 'JPY' => '¥'];
        $currency = self::currency();
        $symbol = (string) apply_filters('latte_shop_currency_symbol', $symbols[$currency] ?? $currency . ' ', $currency);
        $value = function_exists('number_format_i18n') ? number_format_i18n($cents / 100, 2) : number_format($cents / 100, 2);
        return $symbol . $value;
    }
}
