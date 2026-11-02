<?php

/**
 * Recipe comparison of tools/ui8kit3/presets.php (codegen-x checkout vs the pinned compose.json).
 */

declare(strict_types=1);

if (!function_exists('ui8kit3_canonical_recipe')) {
    /**
     * Both sides are json_decode()d: JSON objects compare by keys regardless of their order, lists by position,
     * scalars strictly (1 is not "1", 1 is not 1.0, null is not "").
     */
    function ui8kit3_canonical_recipe(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }
        $value = array_map('ui8kit3_canonical_recipe', $value);
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return $value;
    }

    /**
     * @param array<array-key, mixed> $a
     * @param array<array-key, mixed> $b
     */
    function ui8kit3_same_recipe(array $a, array $b): bool
    {
        return ui8kit3_canonical_recipe($a) === ui8kit3_canonical_recipe($b);
    }
}
