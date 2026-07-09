<?php

/**
 * Twig bindings for the UI8Kit runtime — thin delegates to UI8Kit\Rt and the
 * generated UI8Kit\Classes helpers. Register once per Twig environment:
 *
 *   $twig->addExtension(new \UI8Kit\TwigExtension());
 */

declare(strict_types=1);

namespace UI8Kit;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class TwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('ui8kit_attr_str', [Rt::class, 'attrStr'], ['is_safe' => ['html']]),
            new TwigFunction('ui8kit_or_null', [Rt::class, 'orNull']),
            new TwigFunction('ui8kit_is_set_str', [Rt::class, 'isSetStr']),
            new TwigFunction('ui8kit_default_if_empty', [Rt::class, 'defaultIfEmpty']),
            new TwigFunction('ui8kit_map_or', static fn ($v, array $mapping, string $fallback, bool $lower = false): string => $lower ? Rt::mapOrLower(self::str($v), $mapping, $fallback) : Rt::mapOr(self::str($v), $mapping, $fallback)),
            new TwigFunction('ui8kit_one_of_or', static fn ($v, string $fallback, array $allowed, bool $lower = false): string => $lower ? Rt::oneOfOrLower(self::str($v), $fallback, ...$allowed) : Rt::oneOfOr(self::str($v), $fallback, ...$allowed)),
            new TwigFunction('ui8kit_int_map_or', static fn ($v, array $mapping, string $fallback): string => Rt::intMapOr((int) ($v ?? 0), $mapping, $fallback)),
            new TwigFunction('ui8kit_concat_trim', static fn (array $parts): string => Rt::concatTrim(...array_map(static fn ($p) => (string) ($p ?? ''), $parts))),
            new TwigFunction('ui8kit_cn', static fn (array $parts): string => Rt::cn(...array_map(static fn ($p) => (string) ($p ?? ''), $parts))),
            new TwigFunction('ui8kit_resolve_tag', static fn ($tag, string $fallback, string $group): string => Rt::resolveTag(self::str($tag), $fallback, $group)),
            new TwigFunction('ui8kit_title_tag', static fn ($order): string => Rt::titleTag((int) ($order ?? 0))),
            new TwigFunction('ui8kit_classes', static fn (string $method, array $input = []): string => \call_user_func([Classes::class, $method], $input)),
        ];
    }

    private static function str(mixed $v): string
    {
        return (string) ($v ?? '');
    }
}
