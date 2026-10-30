<?php

/**
 * Demo author profiles (studios that are not WordPress users). Version, compatibility, licenses, changelog,
 * sales and live preview are real product fields now (types/product.php); reviews come from the reviews module.
 *
 * @package WP_FastY_Market
 */

declare(strict_types=1);

namespace WpFastyMarket;

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

    /** @var array<string, array{0: string}> demo product slug => [author slug] */
    private const PRODUCTS = [
        'fasty-agency' => ['fasty-studio'],
        'fasty-store' => ['fasty-studio'],
        'clinic-pro' => ['nordcode'],
        'edu-campus' => ['pixel-forge'],
        'restoran' => ['volga-dev'],
        'latte-magazine' => ['fasty-studio'],
        'folio-noir' => ['pixel-forge'],
        'saas-launch' => ['nordcode'],
        'minimal-blog' => ['volga-dev'],
        'fasty-seo' => ['fasty-studio'],
        'form-forge' => ['pixel-forge'],
        'cache-turbo' => ['nordcode'],
        'booking-kit' => ['volga-dev'],
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
     * Demo author profile of a product (by product slug), until the product's `author` field names a user.
     *
     * @return array<string, mixed>|null
     */
    public static function productAuthor(string $slug): ?array
    {
        $author = self::PRODUCTS[$slug][0] ?? null;
        return $author === null ? null : self::author($author);
    }
}
