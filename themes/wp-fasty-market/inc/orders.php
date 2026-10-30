<?php

/**
 * Orders: private `shop_order` CPT, creation from cart, admin list + status box.
 *
 * @package WP_FastY_Market
 */

declare(strict_types=1);

namespace WpfastyMarket;

final class Orders
{
    public const STATUSES = ['pending', 'processing', 'completed', 'cancelled'];
    public const PAYMENT_METHODS = ['card', 'sbp', 'bank'];

    public static function register(): void
    {
        // Post type itself: types/shop_order.php.
        add_filter('manage_shop_order_posts_columns', static fn (array $c): array => [
            'cb' => $c['cb'] ?? '',
            'title' => __('Order', 'wp-fasty-market'),
            'customer' => __('Customer', 'wp-fasty-market'),
            'total' => __('Total', 'wp-fasty-market'),
            'order_status' => __('Status', 'wp-fasty-market'),
            'date' => $c['date'] ?? __('Date', 'wp-fasty-market'),
        ]);
        add_action('manage_shop_order_posts_custom_column', static function (string $col, int $id): void {
            match ($col) {
                'customer' => print(esc_html(
                    (string) get_post_meta($id, '_order_name', true)
                    . ' <' . get_post_meta($id, '_order_email', true) . '>'
                )),
                'total' => print(esc_html(Money::format((int) get_post_meta($id, '_order_total', true)))),
                'order_status' => print(esc_html(ucfirst((string) get_post_meta($id, '_order_status', true)))),
                default => null,
            };
        }, 10, 2);
        add_action('add_meta_boxes_shop_order', [self::class, 'metaBoxes']);
        add_action('save_post_shop_order', [self::class, 'saveStatus']);
    }

    /**
     * @param array<string, string> $customer sanitized fields
     * @param array{items: list<array<string, mixed>>, subtotal: int, shipping: int, total: int} $summary
     * @return array{id: int, key: string}|null
     */
    public static function create(array $customer, array $summary): ?array
    {
        $key = wp_generate_password(24, false);
        $id = wp_insert_post([
            'post_type' => 'shop_order',
            'post_status' => 'private',
            'post_title' => sprintf('#%s — %s', gmdate('ymd-His'), $customer['name']),
        ], true);
        if (is_wp_error($id)) {
            return null;
        }
        $id = (int) $id;
        wp_update_post(['ID' => $id, 'post_title' => sprintf('#%d — %s', $id, $customer['name'])]);
        foreach ($customer as $field => $value) {
            update_post_meta($id, "_order_{$field}", $value);
        }
        $items = array_map(static fn (array $i): array => [
            'id' => (int) $i['id'], 'title' => (string) $i['title'], 'sku' => (string) $i['sku'],
            'qty' => (int) $i['qty'], 'unit' => (int) $i['unit'], 'line' => (int) $i['line'],
        ], $summary['items']);
        update_post_meta($id, '_order_items', $items);
        update_post_meta($id, '_order_subtotal', $summary['subtotal']);
        update_post_meta($id, '_order_shipping', $summary['shipping']);
        update_post_meta($id, '_order_total', $summary['total']);
        update_post_meta($id, '_order_currency', Money::currency()); // amounts above are integer minor units
        update_post_meta($id, '_order_status', 'pending');
        update_post_meta($id, '_order_key', $key);

        foreach ($items as $item) {
            $stock = Catalog::stock($item['id']);
            if ($stock !== null) {
                Catalog::setStock((int) $item['id'], $stock - (int) $item['qty']);
            }
        }

        do_action('wpfasty_market_order_created', $id);
        wp_mail(
            (string) get_option('admin_email'),
            sprintf(/* translators: %d order id */ __('New order #%d', 'wp-fasty-market'), $id),
            sprintf("%s\n%s\n%s", $customer['name'], $customer['email'], Money::format($summary['total']))
        );
        return ['id' => $id, 'key' => $key];
    }

    /** @return array<string, mixed>|null Order view-model if id+key match. */
    public static function find(int $id, string $key): ?array
    {
        $post = get_post($id);
        $stored = (string) get_post_meta($id, '_order_key', true);
        $isOrder = $post instanceof \WP_Post && $post->post_type === 'shop_order';
        if (!$isOrder || $stored === '' || !hash_equals($stored, $key)) {
            return null;
        }
        $items = (array) get_post_meta($id, '_order_items', true);
        return [
            'id' => $id,
            'name' => (string) get_post_meta($id, '_order_name', true),
            'email' => (string) get_post_meta($id, '_order_email', true),
            'payment' => (string) get_post_meta($id, '_order_payment', true),
            'status' => (string) get_post_meta($id, '_order_status', true),
            'items' => array_map(
                static fn (array $i): array => $i + ['line_display' => Money::format((int) $i['line'])],
                $items
            ),
            'subtotal_display' => Money::format((int) get_post_meta($id, '_order_subtotal', true)),
            'shipping_display' => Money::format((int) get_post_meta($id, '_order_shipping', true)),
            'total_display' => Money::format((int) get_post_meta($id, '_order_total', true)),
        ];
    }

    public static function metaBoxes(): void
    {
        $title = __('Order details', 'wp-fasty-market');
        add_meta_box('wpfasty-market-order', $title, static function (\WP_Post $post): void {
            $id = $post->ID;
            echo '<table class="widefat striped"><thead><tr>'
                . '<th>' . esc_html__('Item', 'wp-fasty-market') . '</th>'
                . '<th>' . esc_html__('Qty', 'wp-fasty-market') . '</th>'
                . '<th>' . esc_html__('Total', 'wp-fasty-market') . '</th>'
                . '</tr></thead><tbody>';
            foreach ((array) get_post_meta($id, '_order_items', true) as $item) {
                printf(
                    '<tr><td>%s</td><td>%d</td><td>%s</td></tr>',
                    esc_html((string) $item['title']),
                    (int) $item['qty'],
                    esc_html(Money::format((int) $item['line']))
                );
            }
            echo '</tbody></table><p>';
            foreach (['name', 'email', 'phone', 'address', 'city', 'postcode', 'country', 'payment', 'notes'] as $f) {
                $v = (string) get_post_meta($id, "_order_{$f}", true);
                if ($v !== '') {
                    printf('<strong>%s:</strong> %s<br>', esc_html(ucfirst($f)), esc_html($v));
                }
            }
            printf(
                '<strong>%s:</strong> %s</p>',
                esc_html__('Total', 'wp-fasty-market'),
                esc_html(Money::format((int) get_post_meta($id, '_order_total', true)))
            );
        }, 'shop_order', 'normal', 'high');

        $title = __('Status', 'wp-fasty-market');
        add_meta_box('wpfasty-market-order-status', $title, static function (\WP_Post $post): void {
            wp_nonce_field('wpfasty_market_order_status', 'wpfasty_market_order_nonce');
            $current = (string) get_post_meta($post->ID, '_order_status', true);
            echo '<select name="order_status" class="widefat">';
            foreach (self::STATUSES as $s) {
                printf(
                    '<option value="%1$s"%2$s>%3$s</option>',
                    esc_attr($s),
                    selected($current, $s, false),
                    esc_html(ucfirst($s))
                );
            }
            echo '</select>';
        }, 'shop_order', 'side');
    }

    public static function saveStatus(int $postId): void
    {
        $nonce = isset($_POST['wpfasty_market_order_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['wpfasty_market_order_nonce']))
            : '';
        if (!wp_verify_nonce($nonce, 'wpfasty_market_order_status') || !current_user_can('edit_post', $postId)) {
            return;
        }
        $status = isset($_POST['order_status']) ? sanitize_key(wp_unslash($_POST['order_status'])) : '';
        if (in_array($status, self::STATUSES, true)) {
            update_post_meta($postId, '_order_status', $status);
        }
    }
}
