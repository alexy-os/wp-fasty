<?php
/**
 * Declarative field: type → sanitizer, input type, meta type.
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Types;

final class Field
{
    public const TYPES = ['string', 'text', 'url', 'email', 'integer', 'number', 'money', 'boolean'];

    /**
     * @param array<string, mixed> $def
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $label,
        public readonly string $metaKey,
        private readonly mixed $sanitize = null,
        public readonly array $def = [],
    ) {
    }

    /**
     * @param array<string, mixed> $def
     */
    public static function fromArray(string $name, array $def, string $prefix): self
    {
        $type = (string) ($def['type'] ?? 'string');
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException(sprintf('Unknown field type "%s" for "%s"', $type, $name));
        }
        return new self(
            $name,
            $type,
            (string) ($def['label'] ?? $name),
            (string) ($def['meta_key'] ?? '_' . $prefix . '_' . $name),
            $def['sanitize'] ?? null,
            $def,
        );
    }

    public function sanitize(mixed $raw): string
    {
        if (is_callable($this->sanitize)) {
            return (string) call_user_func($this->sanitize, $raw);
        }
        $raw = is_scalar($raw) ? trim((string) $raw) : '';
        return match ($this->type) {
            'text' => function_exists('sanitize_textarea_field') ? sanitize_textarea_field($raw) : strip_tags($raw),
            'url' => function_exists('esc_url_raw') ? esc_url_raw($raw) : (filter_var($raw, FILTER_VALIDATE_URL) ? $raw : ''),
            'email' => function_exists('sanitize_email') ? sanitize_email($raw) : (string) filter_var($raw, FILTER_SANITIZE_EMAIL),
            'integer' => $raw === '' || !is_numeric($raw) ? '' : (string) (int) $raw,
            'number', 'money' => self::number($raw),
            'boolean' => in_array(strtolower($raw), ['1', 'on', 'yes', 'true'], true) ? '1' : '',
            default => function_exists('sanitize_text_field') ? sanitize_text_field($raw) : strip_tags($raw),
        };
    }

    public function inputType(): string
    {
        return match ($this->type) {
            'url' => 'url',
            'email' => 'email',
            'integer', 'number', 'money' => 'number',
            'boolean' => 'checkbox',
            default => 'text',
        };
    }

    /** Non-negative decimal with max 2 places, comma accepted; '' when empty/invalid. */
    public static function number(string $raw): string
    {
        $raw = str_replace(',', '.', $raw);
        return $raw === '' || !is_numeric($raw) ? '' : (string) max(0, round((float) $raw, 2));
    }
}
