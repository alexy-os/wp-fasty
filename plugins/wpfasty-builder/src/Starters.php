<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Starter sets of the "New page" panel: the blocks of a demo landing (starters/<id>.json — the `layout` of
 * tools/builder/landings/<id>.json, kept equal by tests/PagesTest.php), put into the Builder draft of a new page.
 * Only blocks: a starter never sets the WordPress page template (Page Attributes → Template).
 * A starter is offered only when it is available on this site: its source is installed, the source's catalog has
 * every block and variant, and every data source it binds (posts, products, projects…) exists.
 * Filter `wpfasty_builder_starters` adds or removes starters (same shape as the files).
 */
final class Starters
{
    /** the demo landings shipped as starters, in the order of the panel */
    public const IDS = ['shop', 'folio', 'press'];

    /** @var list<array{id: string, label: string, description: string, layout: array<string, mixed>}>|null */
    private static ?array $shipped = null;

    /**
     * Every starter file of the plugin, valid or not on this site.
     *
     * @return list<array{id: string, label: string, description: string, layout: array<string, mixed>}>
     */
    public static function shipped(): array
    {
        if (null === self::$shipped) {
            $out = [];
            foreach (self::IDS as $id) {
                $raw = is_file(Plugin::$dir.'/starters/'.$id.'.json')
                    ? file_get_contents(Plugin::$dir.'/starters/'.$id.'.json')
                    : false;
                $s = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($s) && is_array($s['layout'] ?? null) && is_array($s['layout']['blocks'] ?? null)) {
                    $out[] = [
                        'id' => $id,
                        'label' => Cast::string($s['label'] ?? $id),
                        'description' => Cast::string($s['description'] ?? ''),
                        'layout' => Cast::map($s['layout']),
                    ];
                }
            }
            self::$shipped = function_exists('apply_filters')
                ? self::starters(apply_filters('wpfasty_builder_starters', $out))
                : $out;
        }

        return self::$shipped;
    }

    /**
     * Whether a starter can be built here: every block / variant is in the catalog of its source, every bound data
     * source exists.
     *
     * @param array<string, mixed> $starter
     * @param list<string>         $dataSources
     */
    public static function fits(array $starter, ?Catalog $catalog, array $dataSources): bool
    {
        $blocks = Cast::path($starter, 'layout', 'blocks');
        if (null === $catalog || !is_array($blocks) || [] === $blocks) {
            return false;
        }
        foreach ($blocks as $b) {
            $variant = is_array($b)
                ? $catalog->variant(Cast::string($b['block'] ?? ''), Cast::string($b['variant'] ?? ''))
                : null;
            if (null === $variant) {
                return false;
            }
            foreach ((array) ($b['bindings'] ?? []) as $bind) {
                if (!is_array($bind) || !in_array(Cast::string($bind['source'] ?? ''), $dataSources, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * The starters available on this site.
     *
     * @return list<array{id: string, label: string, description: string, layout: array<string, mixed>}>
     */
    public static function available(): array
    {
        $out = [];
        $data = null;
        foreach (self::shipped() as $s) {
            $catalog = Sources::catalog(sanitize_key(Cast::string($s['layout']['source'] ?? 'ui8kit')));
            $data ??= array_keys(DataSources::all($catalog));
            if (self::fits($s, $catalog, $data)) {
                $out[] = $s;
            }
        }

        return $out;
    }

    /** @return array{id: string, label: string, description: string, layout: array<string, mixed>}|null */
    public static function get(string $id): ?array
    {
        foreach (self::available() as $s) {
            if ($s['id'] === $id) {
                return $s;
            }
        }

        return null;
    }

    /**
     * What the panel shows: id, label, description and the number of blocks of each available starter.
     *
     * @return list<array{id: string, label: string, description: string, blocks: int}>
     */
    public static function forEditor(): array
    {
        return array_map(static fn (array $s): array => [
            'id' => $s['id'],
            'label' => $s['label'],
            'description' => $s['description'],
            'blocks' => count(Cast::list($s['layout']['blocks'] ?? [])),
        ], self::available());
    }

    /**
     * Starters returned by the `wpfasty_builder_starters` filter, read back typed (rows without a layout dropped).
     *
     * @return list<array{id: string, label: string, description: string, layout: array<string, mixed>}>
     */
    private static function starters(mixed $filtered): array
    {
        $out = [];
        foreach (Cast::list($filtered) as $s) {
            if (is_array($s) && is_array($s['layout'] ?? null)) {
                $id = Cast::string($s['id'] ?? '');
                $out[] = [
                    'id' => $id,
                    'label' => Cast::string($s['label'] ?? $id),
                    'description' => Cast::string($s['description'] ?? ''),
                    'layout' => Cast::map($s['layout']),
                ];
            }
        }

        return $out;
    }
}
