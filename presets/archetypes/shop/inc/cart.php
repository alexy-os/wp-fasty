<?php

/**
 * Cart stored in an HMAC-signed cookie (no sessions, no DB writes for guests).
 * Prices are never trusted from the client — always recomputed from product meta.
 *
 * @package Latte_Shop
 */

declare(strict_types=1);

namespace LatteShop;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- cookie is HMAC-verified in decode().

final class Cart
{
    public const COOKIE = 'latte_shop_cart';
    public const MAX_QTY = 99;
    public const MAX_LINES = 50;

    /** @var array<int, int>|null product id => qty */
    private static ?array $lines = null;

    /** @return array<int, int> */
    public static function lines(): array
    {
        if (self::$lines !== null) {
            return self::$lines;
        }
        self::$lines = self::decode(isset($_COOKIE[self::COOKIE]) ? (string) wp_unslash($_COOKIE[self::COOKIE]) : '');
        return self::$lines;
    }

    public static function add(int $id, int $qty): bool
    {
        if (!Catalog::isPurchasable($id)) {
            return false;
        }
        $lines = self::lines();
        if (!isset($lines[$id]) && count($lines) >= self::MAX_LINES) {
            return false;
        }
        $lines[$id] = ($lines[$id] ?? 0) + max(1, $qty);
        self::$lines = self::clamp($lines);
        return true;
    }

    public static function set(int $id, int $qty): void
    {
        $lines = self::lines();
        if ($qty <= 0) {
            unset($lines[$id]);
        } elseif (isset($lines[$id])) {
            $lines[$id] = $qty;
        }
        self::$lines = self::clamp($lines);
    }

    public static function clear(): void
    {
        self::$lines = [];
    }

    public static function count(): int
    {
        return array_sum(self::lines());
    }

    /**
     * Priced cart view-model (server-side prices only).
     *
     * @return array{items: list<array<string, mixed>>, count: int, subtotal: int, shipping: int, total: int}
     */
    public static function summary(): array
    {
        $items = [];
        $subtotal = 0;
        foreach (self::lines() as $id => $qty) {
            if (!Catalog::isPurchasable($id)) {
                continue;
            }
            $unit = Catalog::price($id);
            $line = $unit * $qty;
            $subtotal += $line;
            $thumb = get_post_thumbnail_id($id);
            $items[] = [
                'id' => $id,
                'title' => html_entity_decode(get_the_title($id), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'url' => (string) get_permalink($id),
                'qty' => $qty,
                'unit' => $unit,
                'line' => $line,
                'unit_display' => Money::format($unit),
                'line_display' => Money::format($line),
                'image' => $thumb ? (string) wp_get_attachment_image_url((int) $thumb, 'thumbnail') : '',
                'sku' => wpfasty_field($id, 'sku'),
            ];
        }
        $shipping = self::shipping($subtotal);
        return [
            'items' => $items,
            'count' => array_sum(array_column($items, 'qty')),
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $subtotal + $shipping,
        ];
    }

    public static function shipping(int $subtotal): int
    {
        $free = (int) apply_filters('latte_shop_free_shipping_cents', 10000);
        $flat = (int) apply_filters('latte_shop_flat_shipping_cents', 500);
        return $subtotal === 0 || $subtotal >= $free ? 0 : $flat;
    }

    /** Persist current lines to the signed cookie (call before redirect). */
    public static function persist(): void
    {
        $value = self::encode(self::lines());
        $opts = [
            'expires' => $value === '' ? time() - 3600 : time() + 30 * DAY_IN_SECONDS,
            'path' => COOKIEPATH ?: '/',
            'domain' => COOKIE_DOMAIN ?: '',
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];
        setcookie(self::COOKIE, $value, $opts);
        $_COOKIE[self::COOKIE] = $value;
    }

    /** @param array<int, int> $lines */
    public static function encode(array $lines): string
    {
        $lines = self::clamp($lines);
        if ($lines === []) {
            return '';
        }
        // Cookie-safe compact payload: "id:qty,id:qty" (HMAC-signed below).
        $payload = implode(',', array_map(
            static fn (int $id, int $qty): string => $id . ':' . $qty,
            array_keys($lines),
            $lines
        ));
        return $payload . '.' . self::sign($payload);
    }

    /** @return array<int, int> */
    public static function decode(string $raw): array
    {
        $parts = explode('.', $raw, 2);
        if (count($parts) !== 2 || !hash_equals(self::sign($parts[0]), $parts[1])) {
            return [];
        }
        if (!preg_match('/^\d+:\d+(?:,\d+:\d+)*$/', $parts[0])) {
            return [];
        }
        $lines = [];
        foreach (explode(',', $parts[0]) as $pair) {
            [$id, $qty] = array_map('intval', explode(':', $pair, 2));
            $lines[$id] = $qty;
        }
        return self::clamp($lines);
    }

    private static function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, wp_salt('auth') . self::COOKIE);
    }

    /**
     * @param array<int, int> $lines
     * @return array<int, int>
     */
    private static function clamp(array $lines): array
    {
        $out = [];
        foreach ($lines as $id => $qty) {
            $id = (int) $id;
            $qty = min(self::MAX_QTY, (int) $qty);
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $stock = function_exists('get_post_meta') ? Catalog::stock($id) : null;
            $out[$id] = $stock === null ? $qty : min($qty, $stock);
            if ($out[$id] <= 0) {
                unset($out[$id]);
            }
            if (count($out) >= self::MAX_LINES) {
                break;
            }
        }
        return $out;
    }
}
