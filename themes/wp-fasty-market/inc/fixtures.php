<?php

/**
 * Demo data for digital-goods fields that the Product type does not declare yet
 * (version, compatibility, licenses, changelog, author, sales, live preview).
 *
 * Presentation-only fixtures read by the context filter. The real fields belong in the
 * wpfasty-types Product declaration; once they exist, Fixtures::product() is replaced by field reads.
 *
 * @package WP_FastY_Market
 */

declare(strict_types=1);

namespace WpfastyMarket;

final class Fixtures
{
    /** @var array<string, array<string, string|int|float>> author slug => profile */
    private const AUTHORS = [
        'fasty-studio' => [
            'name' => 'FastY Studio',
            'slug' => 'fasty-studio',
            'role' => 'Elite author',
            'items' => 42,
            'sales' => 18240,
            'rating' => 4.9,
            'since' => '2019',
        ],
        'nordcode' => [
            'name' => 'NordCode',
            'slug' => 'nordcode',
            'role' => 'Top seller',
            'items' => 17,
            'sales' => 6310,
            'rating' => 4.8,
            'since' => '2021',
        ],
        'pixel-forge' => [
            'name' => 'Pixel Forge',
            'slug' => 'pixel-forge',
            'role' => 'Power author',
            'items' => 23,
            'sales' => 9820,
            'rating' => 4.7,
            'since' => '2020',
        ],
        'volga-dev' => [
            'name' => 'Volga Dev',
            'slug' => 'volga-dev',
            'role' => 'New author',
            'items' => 6,
            'sales' => 740,
            'rating' => 4.6,
            'since' => '2025',
        ],
    ];

    /** @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: int}> [author, version, wp, php, sales] */
    private const PRODUCTS = [
        'fasty-agency' => ['fasty-studio', '3.2.0', '6.4', '8.2', 2140],
        'fasty-store' => ['fasty-studio', '2.8.1', '6.5', '8.2', 1890],
        'clinic-pro' => ['nordcode', '1.6.0', '6.4', '8.1', 640],
        'edu-campus' => ['pixel-forge', '2.1.3', '6.3', '8.1', 910],
        'restoran' => ['volga-dev', '1.0.4', '6.5', '8.2', 120],
        'latte-magazine' => ['fasty-studio', '4.0.0', '6.4', '8.2', 3320],
        'folio-noir' => ['pixel-forge', '1.9.0', '6.4', '8.1', 1280],
        'saas-launch' => ['nordcode', '2.3.2', '6.5', '8.2', 1540],
        'minimal-blog' => ['volga-dev', '1.2.0', '6.3', '8.0', 410],
        'fasty-seo' => ['fasty-studio', '5.1.2', '6.4', '8.1', 4870],
        'form-forge' => ['pixel-forge', '3.0.1', '6.4', '8.1', 2260],
        'cache-turbo' => ['nordcode', '1.8.0', '6.5', '8.2', 1730],
        'booking-kit' => ['volga-dev', '0.9.5', '6.5', '8.2', 210],
    ];

    /** @return list<array<string, mixed>> */
    public static function authors(): array
    {
        return array_map(
            static fn (array $a): array => $a + ['url' => self::authorUrl((string) $a['slug'])],
            array_values(self::AUTHORS)
        );
    }

    /** @return array<string, mixed>|null */
    public static function author(string $slug): ?array
    {
        return isset(self::AUTHORS[$slug]) ? self::AUTHORS[$slug] + ['url' => self::authorUrl($slug)] : null;
    }

    public static function authorUrl(string $slug): string
    {
        return add_query_arg('a', $slug, Front::pageUrl('author'));
    }

    /** Products of an author (slugs). @return list<string> */
    public static function authorProducts(string $slug): array
    {
        return array_keys(array_filter(self::PRODUCTS, static fn (array $p): bool => $p[0] === $slug));
    }

    /**
     * Digital-goods view model of one product.
     *
     * @return array<string, mixed>
     */
    public static function product(string $slug, int $priceCents): array
    {
        [$author, $version, $wp, $php, $sales] = self::PRODUCTS[$slug] ?? ['fasty-studio', '1.0.0', '6.4', '8.2', 0];
        return [
            'version' => $version,
            'wp' => $wp,
            'php' => $php,
            'sales' => $sales,
            'updated' => '2026-10-' . str_pad((string) (1 + strlen($slug) % 28), 2, '0', STR_PAD_LEFT),
            'author' => self::author($author),
            'preview_url' => home_url('/demo/' . $slug . '/'),
            'licenses' => [
                ['id' => 'regular', 'price' => Money::format($priceCents)],
                ['id' => 'extended', 'price' => Money::format($priceCents * 5)],
            ],
            'changelog' => [
                [
                    'version' => $version,
                    'date' => '2026-10-12',
                    'notes' => __('PHP 8.5 compatibility, faster first paint, new header layouts.', 'wp-fasty-market'),
                ],
                ['version' => '1.0.0', 'date' => '2025-06-02', 'notes' => __('Initial release.', 'wp-fasty-market')],
            ],
            'reviews' => [
                [
                    'name' => 'Anna K.',
                    'rating' => 5,
                    'text' => __(
                        'Installed in ten minutes, the demo import matched the preview exactly.',
                        'wp-fasty-market'
                    ),
                ],
                [
                    'name' => 'Dmitry S.',
                    'rating' => 5,
                    'text' => __(
                        'Clean code, no page builder lock-in, support answered within a day.',
                        'wp-fasty-market'
                    ),
                ],
                [
                    'name' => 'Olga M.',
                    'rating' => 4,
                    'text' => __('Great design system. I would like more color presets.', 'wp-fasty-market'),
                ],
            ],
        ];
    }
}
