<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Narrowing of untyped values (options, meta, decoded JSON, request data) to scalars: the same result as a cast
 * for scalars and null, a string for Stringable, and an empty value for arrays and other objects (where a cast would
 * emit "Array" with a warning or fail).
 */
final class Cast
{
    public static function string(mixed $value): string
    {
        if (\is_string($value)) {
            return $value;
        }
        if (null === $value || \is_scalar($value) || $value instanceof \Stringable) {
            return (string) $value;
        }

        return '';
    }

    public static function int(mixed $value): int
    {
        if (\is_int($value)) {
            return $value;
        }

        return null === $value || \is_scalar($value) ? (int) $value : 0;
    }

    public static function float(mixed $value): float
    {
        return null === $value || \is_scalar($value) ? (float) $value : 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    public static function map(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $key => $item) {
            $out[(string) $key] = $item;
        }

        return $out;
    }

    /**
     * @return list<mixed>
     */
    public static function list(mixed $value): array
    {
        return \is_array($value) ? array_values($value) : [];
    }

    /**
     * @return list<string>
     */
    public static function strings(mixed $value): array
    {
        return array_map(self::string(...), self::list($value));
    }

    /**
     * `$value[$k1][$k2]…` of nested arrays, null when a level is missing or not an array (the `?? null` of a typed
     * read).
     */
    public static function path(mixed $value, string|int ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (!\is_array($value) || !\array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }
}
