<?php
/**
 * Class presets of the WP FastY kit over UI8Kit 3 primitives.
 *
 * UI8Kit 3 primitives are unstyled (`\UI8Kit\Latte\Classes::buttonClasses()` only merges `class`), so variants live
 * in our presets (`presets/components/*.json`, compiled to `kit/presets.json` by `php tools/ui8kit3/presets.php`);
 * alert, badge, button, card, iconbadge, input, textarea, label, link, stack and title are the codegen-x kit-compose
 * recipes (`packages/kit-compose/variants/*.variants.json`), imported unchanged.
 * Latte: `class: kit('button', ['variant' => 'default', 'size' => 'lg'], 'mt-4')`, parts as `kit('card.CardHeader')`.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Template;

final class Kit
{
    /** @var array<string, array<string, mixed>>|null */
    private static ?array $presets = null;

    /**
     * Base + the selected (or default) value of every recipe key + the matching compound classes + extra classes, in
     * that order (kit-compose `Compose::rc` order; the primitive merges the result with tailwind-merge).
     *
     * @param array<string, scalar|null> $selection
     */
    public static function classes(string $component, array $selection = [], ?string ...$extra): string
    {
        $recipe = self::recipe($component);
        $parts = [trim((string) ($recipe['base'] ?? ''))];
        $picked = [];
        foreach ((array) ($recipe['keys'] ?? []) as $key) {
            $choice = trim((string) ($selection[$key] ?? ''));
            if ($choice === '') {
                $choice = trim((string) ($recipe['defaults'][$key] ?? ''));
            }
            $picked[$key] = $choice;
            $parts[] = $choice === '' ? '' : trim((string) ($recipe['byKey'][$key][$choice] ?? ''));
        }
        // Compound classes of a codegen-x recipe (kit-compose `compound`): added when every listed key has that value.
        foreach ((array) ($recipe['compound'] ?? []) as $when) {
            $hit = true;
            foreach ((array) $when as $key => $value) {
                if ($key !== 'class' && ($picked[$key] ?? '') !== (string) $value) {
                    $hit = false;
                    break;
                }
            }
            if ($hit) {
                $parts[] = trim((string) ($when['class'] ?? ''));
            }
        }
        foreach ($extra as $c) {
            $parts[] = trim((string) $c);
        }

        return implode(' ', array_values(array_filter($parts, static fn (string $p): bool => $p !== '')));
    }

    /** @return array<string, mixed> */
    public static function recipe(string $component): array
    {
        self::$presets ??= (array) json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/kit/presets.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        [$name, $part] = array_pad(explode('.', $component, 2), 2, null);
        $recipe = self::$presets[$name]
            ?? throw new \InvalidArgumentException("Unknown kit component {$component}");

        return $part === null
            ? $recipe
            : (array) ($recipe['parts'][$part]
                ?? throw new \InvalidArgumentException("Unknown kit part {$component}"));
    }
}
