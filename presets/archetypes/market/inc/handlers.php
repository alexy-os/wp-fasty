<?php

/**
 * Form handlers (admin-post.php): cart operations and checkout.
 * Every request: nonce check, sanitize, server-side pricing, safe redirect.
 *
 * @package WP_FastY_Market
 */

declare(strict_types=1);

namespace WpfastyMarket;

// phpcs:disable WordPress.Security.NonceVerification -- every handler calls self::verify() first.

final class Handlers
{
    public static function register(): void
    {
        foreach (['wpfasty_market_cart' => 'cart', 'wpfasty_market_checkout' => 'checkout'] as $action => $method) {
            add_action("admin_post_{$action}", [self::class, $method]);
            add_action("admin_post_nopriv_{$action}", [self::class, $method]);
        }
    }

    public static function cart(): void
    {
        self::verify('wpfasty_market_cart');
        $op = isset($_POST['op']) ? sanitize_key(wp_unslash($_POST['op'])) : '';
        $id = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
        $qty = isset($_POST['qty']) ? (int) wp_unslash($_POST['qty']) : 1;
        $notice = 'updated';

        switch ($op) {
            case 'add':
                $notice = Cart::add($id, max(1, min(Cart::MAX_QTY, $qty))) ? 'added' : 'unavailable';
                break;
            case 'update':
                $qtys = isset($_POST['qtys']) && is_array($_POST['qtys']) ? wp_unslash($_POST['qtys']) : [];
                foreach ($qtys as $pid => $q) {
                    Cart::set(absint($pid), max(0, min(Cart::MAX_QTY, (int) $q)));
                }
                break;
            case 'remove':
                Cart::set($id, 0);
                $notice = 'removed';
                break;
            default:
                wp_die(esc_html__('Unknown cart action.', 'wpfasty-market'), '', ['response' => 400]);
        }
        Cart::persist();

        $target = $op === 'add' && isset($_POST['buy_now']) ? Front::pageUrl('checkout') : Front::pageUrl('cart');
        if ($op === 'add' && $notice === 'unavailable') {
            $target = wp_get_referer() ?: Front::shopUrl();
        }
        wp_safe_redirect(add_query_arg('cart', $notice, $target));
        exit;
    }

    public static function checkout(): void
    {
        self::verify('wpfasty_market_checkout');
        $summary = Cart::summary();
        $back = Front::pageUrl('checkout');
        if ($summary['items'] === []) {
            wp_safe_redirect(add_query_arg('cart', 'empty', Front::pageUrl('cart')));
            exit;
        }

        $fields = ['name', 'email', 'phone', 'address', 'city', 'postcode', 'country', 'notes', 'payment'];
        $c = [];
        foreach ($fields as $f) {
            $raw = isset($_POST[$f]) ? (string) wp_unslash($_POST[$f]) : '';
            $c[$f] = match ($f) {
                'email' => sanitize_email($raw),
                'notes', 'address' => sanitize_textarea_field($raw),
                'payment' => sanitize_key($raw),
                default => sanitize_text_field($raw),
            };
        }

        $errors = [];
        // Digital goods: no postal address; the offer and personal-data consents are mandatory (152-FZ).
        $c['consent_offer'] = isset($_POST['consent_offer']) ? 'yes' : '';
        $c['consent_pd'] = isset($_POST['consent_pd']) ? 'yes' : '';
        $c['consent_news'] = isset($_POST['consent_news']) ? 'yes' : '';
        foreach (['name', 'email', 'consent_offer', 'consent_pd'] as $required) {
            if ($c[$required] === '') {
                $errors[] = $required;
            }
        }
        if ($c['email'] !== '' && !is_email($c['email'])) {
            $errors[] = 'email';
        }
        if (!in_array($c['payment'], Orders::PAYMENT_METHODS, true)) {
            $errors[] = 'payment';
        }
        if ($errors !== []) {
            // Keep non-sensitive input across the redirect for 10 minutes, keyed by a random token.
            $token = wp_generate_password(16, false);
            set_transient('wpfasty_market_co_' . $token, $c, 10 * MINUTE_IN_SECONDS);
            $args = ['co_errors' => implode(',', array_unique($errors)), 'co' => $token];
            wp_safe_redirect(add_query_arg($args, $back));
            exit;
        }

        $order = Orders::create($c, $summary);
        if ($order === null) {
            wp_safe_redirect(add_query_arg('co_errors', 'server', $back));
            exit;
        }
        self::logConsent((int) $order['id'], $c);
        Cart::clear();
        Cart::persist();
        wp_safe_redirect(add_query_arg(['order' => $order['id'], 'key' => $order['key']], $back));
        exit;
    }

    /**
     * Consent log on the order (152-FZ art. 9, 38-FZ art. 18): time, IP, document versions, ticks.
     *
     * @param array<string, string> $c
     */
    private static function logConsent(int $orderId, array $c): void
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        update_post_meta($orderId, '_order_consent_log', [
            'time' => gmdate('c'),
            'ip' => $ip,
            'version' => (string) apply_filters('wpfasty_market_legal_version', '2026-10-30'),
            'offer' => $c['consent_offer'] === 'yes',
            'personal_data' => $c['consent_pd'] === 'yes',
            'newsletter' => $c['consent_news'] === 'yes',
        ]);
    }

    private static function verify(string $action): void
    {
        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !wp_verify_nonce($nonce, $action)) {
            wp_die(
                esc_html__('Your session expired. Please go back and try again.', 'wpfasty-market'),
                '',
                ['response' => 403, 'back_link' => true]
            );
        }
    }
}
