<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use WP_Post;
use WP_Term;

/**
 * WordPress data for bound block paths. A source reads posts, projects, products or terms; each item becomes
 * a record of the data kind the block expects (the record fields of the catalog: Post, Product, Project,
 * Feature, Category, PostTopic...). Dates use the site date format, money stays in minor units + currency.
 *
 * Field facts (verified in this repo): products `_shop_price`, `_shop_sale_price` (cents), `_shop_stock`
 * (empty = unlimited), taxonomy `product_cat`, currency option `wpfasty_currency`; projects `_folio_client`,
 * `_folio_year`, `_folio_role`, `_folio_url`, taxonomy `project_type`.
 */
final class DataSources
{
    /** data kinds each source can fill */
    private const ITEM_KINDS = ['post', 'project', 'product', 'feature', 'value', 'tile', 'milestone', 'photo'];
    private const TERM_KINDS = ['category', 'topic', 'posttag'];

    /**
     * The sources available on this site.
     *
     * @return array<string, array{id: string, label: string, kinds: list<string>, type: string, object: string}>
     */
    public static function all(): array
    {
        $out = [];
        $add = static function (
            string $id,
            string $label,
            string $type,
            string $object,
            array $kinds,
        ) use (&$out): void {
            $out[$id] = ['id' => $id, 'label' => $label, 'kinds' => $kinds, 'type' => $type, 'object' => $object];
        };
        $add('posts', __('Posts', 'wpfasty-builder'), 'post_type', 'post', self::ITEM_KINDS);
        if (post_type_exists('project')) {
            $add('projects', __('Projects', 'wpfasty-builder'), 'post_type', 'project', self::ITEM_KINDS);
        }
        if (post_type_exists('product')) {
            $add('products', __('Products', 'wpfasty-builder'), 'post_type', 'product', self::ITEM_KINDS);
        }
        $add('pages', __('Pages', 'wpfasty-builder'), 'post_type', 'page', self::ITEM_KINDS);
        $add('categories', __('Post categories', 'wpfasty-builder'), 'taxonomy', 'category', self::TERM_KINDS);
        if (taxonomy_exists('project_type')) {
            $add('project_types', __('Project types', 'wpfasty-builder'), 'taxonomy', 'project_type', self::TERM_KINDS);
        }
        if (taxonomy_exists('product_cat')) {
            $add(
                'product_cats',
                __('Product categories', 'wpfasty-builder'),
                'taxonomy',
                'product_cat',
                self::TERM_KINDS,
            );
        }
        return (array) apply_filters('wpfasty_builder_data_sources', $out);
    }

    /**
     * Records for one binding.
     *
     * @param array{source: string, limit?: int, term?: string} $binding
     * @return list<array<string, mixed>>
     */
    public static function resolve(array $binding, string $kind): array
    {
        $src = self::all()[$binding['source']] ?? null;
        if ($src === null) {
            return [];
        }
        $limit = max(1, min(24, (int) ($binding['limit'] ?? 6)));
        $term = (string) ($binding['term'] ?? '');
        if ($src['type'] === 'taxonomy') {
            $terms = get_terms([
                'taxonomy' => $src['object'],
                'hide_empty' => true,
                'number' => $limit,
                'orderby' => 'count',
                'order' => 'DESC',
            ]);
            $out = [];
            foreach (is_array($terms) ? $terms : [] as $t) {
                if ($t instanceof WP_Term) {
                    $out[] = self::termRecord($t, $kind);
                }
            }
            return $out;
        }
        $args = [
            'post_type' => $src['object'],
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'orderby' => $src['object'] === 'post' ? 'date' : ['menu_order' => 'ASC', 'date' => 'DESC'],
        ];
        if ($src['object'] === 'page') {
            $args['post_parent'] = 0;
        }
        $tax = ['post' => 'category', 'project' => 'project_type', 'product' => 'product_cat'][$src['object']] ?? '';
        if ($term !== '' && $tax !== '') {
            $args['tax_query'] = [['taxonomy' => $tax, 'field' => 'slug', 'terms' => $term]];
        }
        $out = [];
        foreach (get_posts($args) as $post) {
            if ($post instanceof WP_Post) {
                $out[] = self::record(self::item($post, $tax), $kind);
            }
        }
        return $out;
    }

    /**
     * The neutral item of a post: all fields any record kind can take.
     *
     * @return array<string, mixed>
     */
    public static function item(WP_Post $post, string $tax): array
    {
        $terms = $tax !== '' ? get_the_terms($post, $tax) : false;
        $first = is_array($terms) && isset($terms[0]) && $terms[0] instanceof WP_Term ? $terms[0] : null;
        $words = str_word_count(wp_strip_all_tags((string) $post->post_content));
        $image = has_post_thumbnail($post) ? (string) get_the_post_thumbnail_url($post, 'large') : '';
        $excerpt = has_excerpt($post) ? (string) get_the_excerpt($post) : wp_trim_words(
            wp_strip_all_tags(strip_shortcodes((string) $post->post_content)),
            24,
        );
        $item = [
            'id' => $post->post_type . '-' . $post->ID,
            'title' => get_the_title($post),
            'excerpt' => html_entity_decode($excerpt, ENT_QUOTES, 'UTF-8'),
            'category' => $first?->name ?? '',
            'categorySlug' => $first?->slug ?? '',
            'tags' => is_array($terms) ? array_values(array_map(
                static fn (WP_Term $t): string => $t->name,
                $terms,
            )) : [],
            'author' => (string) get_the_author_meta('display_name', (int) $post->post_author),
            'date' => (string) get_the_date('', $post),
            'datetime' => (string) get_post_time('c', true, $post),
            'minutes' => max(1, (int) ceil($words / 200)),
            'href' => (string) get_permalink($post),
            'image' => $image,
            'type' => $post->post_type,
        ];
        if ($post->post_type === 'project') {
            $item['client'] = (string) get_post_meta($post->ID, '_folio_client', true);
            $item['year'] = (string) get_post_meta($post->ID, '_folio_year', true);
            $item['role'] = (string) get_post_meta($post->ID, '_folio_role', true);
        }
        if ($post->post_type === 'product') {
            $price = (int) get_post_meta($post->ID, '_shop_price', true);
            $sale = (string) get_post_meta($post->ID, '_shop_sale_price', true);
            $stock = (string) get_post_meta($post->ID, '_shop_stock', true);
            $item['price'] = $sale !== '' && (int) $sale > 0 && (int) $sale < $price ? (int) $sale : $price;
            $item['onSale'] = $item['price'] < $price;
            $item['currency'] = strtoupper((string) get_option('wpfasty_currency', 'USD'));
            $item['inStock'] = $stock === '' || (int) $stock > 0;
        }
        return $item;
    }

    /**
     * A record of a data kind from a neutral item.
     *
     * @param array<string, mixed> $i
     * @return array<string, mixed>
     */
    public static function record(array $i, string $kind): array
    {
        $icon = [
            'post' => 'file-text',
            'project' => 'briefcase',
            'product' => 'shopping-bag',
            'page' => 'file',
        ][$i['type'] ?? ''] ?? 'file-text';
        $project = ($i['type'] ?? '') === 'project';
        return match ($kind) {
            'post' => [
                'id' => $i['id'], 'title' => $i['title'], 'excerpt' => $i['excerpt'], 'category' => $i['category'],
                'author' => $project && ($i['client'] ?? '') !== '' ? $i['client'] : $i['author'],
                'date' => $project && ($i['year'] ?? '') !== '' ? $i['year'] : $i['date'],
                'minutes' => $i['minutes'], 'icon' => $icon, 'href' => $i['href'], 'image' => $i['image'],
            ],
            'product' => [
                'id' => $i['id'], 'name' => $i['title'], 'summary' => $i['excerpt'],
                'price' => (int) ($i['price'] ?? 0),
                'currency' => (string) ($i['currency'] ?? strtoupper((string) get_option('wpfasty_currency', 'USD'))),
                'rating' => 0, 'reviews' => 0, 'badge' => !empty($i['onSale']) ? 'sale' : '',
                'category' => $i['category'],
                'inStock' => (bool) ($i['inStock'] ?? true), 'href' => $i['href'], 'image' => $i['image'],
            ],
            'project' => [
                'id' => $i['id'], 'title' => $i['title'], 'category' => $i['category'],
                'year' => (string) ($i['year'] ?? substr((string) $i['datetime'], 0, 4)), 'summary' => $i['excerpt'],
                'tags' => $i['tags'], 'featured' => false, 'client' => (string) ($i['client'] ?? ''),
            ],
            'feature', 'value' => ['id' => $i['id'], 'title' => $i['title'], 'text' => $i['excerpt'], 'icon' => $icon],
            'tile' => [
                'id' => $i['id'],
                'title' => $i['title'],
                'text' => $i['excerpt'],
                'icon' => $icon,
                'size' => 'small',
            ],
            'milestone' => [
                'id' => $i['id'],
                'date' => $i['date'],
                'title' => $i['title'],
                'text' => $i['excerpt'],
                'tag' => $i['category'],
            ],
            'photo' => ['id' => $i['id'], 'title' => $i['title'], 'tag' => $i['category'], 'size' => 'small'],
            default => $i,
        };
    }

    /** @return array<string, mixed> */
    public static function termRecord(WP_Term $t, string $kind): array
    {
        return match ($kind) {
            'topic' => ['id' => $t->taxonomy . '-' . $t->term_id, 'title' => $t->name, 'value' => $t->slug],
            'posttag' => ['id' => $t->taxonomy . '-' . $t->term_id, 'name' => $t->name, 'icon' => 'tag'],
            default => [
                'id' => $t->taxonomy . '-' . $t->term_id,
                'name' => $t->name,
                'icon' => 'folder',
                'count' => (int) $t->count,
            ],
        };
    }
}
