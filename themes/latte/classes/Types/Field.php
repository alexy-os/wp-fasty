<?php
/**
 * Declarative field: type → sanitizer, input type, meta type.
 * Storage is typed: integer → int, number → float, boolean → bool, money → int minor units (cents),
 * everything else → string. The admin form shows money as a decimal (12.50) and stores cents (1250).
 * date → ISO calendar date string `YYYY-MM-DD` (no time, no timezone).
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Types;

final class Field
{
    public const TYPES = ['string', 'text', 'url', 'email', 'integer', 'number', 'money', 'boolean', 'date'];

    /** REST schema pattern of a `date` field value. */
    public const DATE_PATTERN = '^\\d{4}-\\d{2}-\\d{2}$';

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
            'url' => self::url($raw),
            'email' => function_exists('sanitize_email')
                ? sanitize_email($raw)
                : (string) filter_var($raw, FILTER_SANITIZE_EMAIL),
            'integer' => $raw === '' || !is_numeric($raw) ? '' : (string) (int) $raw,
            'number' => self::number($raw),
            'money' => self::number($raw, true),
            'boolean' => in_array(strtolower($raw), ['1', 'on', 'yes', 'true'], true) ? '1' : '',
            'date' => self::date($raw),
            default => self::option(
                function_exists('sanitize_text_field') ? sanitize_text_field($raw) : strip_tags($raw),
                $this->options(),
            ),
        };
    }

    /**
     * Closed value set of the field (site manifest `options`; Codex type select); [] = any value.
     *
     * @return list<string>
     */
    public function options(): array
    {
        return array_values(array_map('strval', (array) ($this->def['options'] ?? [])));
    }

    /** @param list<string> $options */
    private static function option(string $value, array $options): string
    {
        return $options === [] || in_array($value, $options, true) ? $value : '';
    }

    /** register_post_meta() type for this field. */
    public function metaType(): string
    {
        return match ($this->type) {
            'integer', 'money' => 'integer',
            'number' => 'number',
            'boolean' => 'boolean',
            default => 'string',
        };
    }

    /** Admin form value (money as a decimal) → typed storage value; null when empty (meta is deleted). */
    public function fromInput(mixed $raw): int|float|bool|string|null
    {
        $clean = $this->sanitize($raw);
        if ($this->type === 'boolean') {
            return $clean === '1';
        }
        if ($clean === '') {
            return null;
        }
        return match ($this->type) {
            'money' => (int) round(((float) $clean) * 100),
            'integer' => (int) $clean,
            'number' => (float) $clean,
            default => $clean,
        };
    }

    /**
     * REST / programmatic value (money already in cents) → typed storage value. Used as the meta sanitize_callback.
     * A non-numeric value of a numeric field is "no value" (null), never 0: an empty sale price stays unset.
     * Money may be negative (refunds, discounts); integer and number stay non-negative.
     */
    public function toStorage(mixed $value): int|float|bool|string|null
    {
        $num = is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)));
        return match ($this->type) {
            'money' => $num ? (int) round((float) $value) : null,
            'integer' => $num ? max(0, (int) round((float) $value)) : null,
            'number' => $num ? max(0.0, round((float) $value, 2)) : null,
            'boolean' => in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true),
            default => $this->sanitize($value),
        };
    }

    /** Stored value → admin form value (money cents → decimal string). */
    public function toInput(mixed $stored): string
    {
        if ($stored === '' || $stored === null) {
            return '';
        }
        return match ($this->type) {
            'money' => number_format(((int) $stored) / 100, 2, '.', ''),
            'boolean' => $stored ? '1' : '',
            default => (string) $stored,
        };
    }

    public function inputType(): string
    {
        if ($this->options() !== []) {
            return 'select';
        }
        return match ($this->type) {
            'url' => 'url',
            'email' => 'email',
            'integer', 'number', 'money' => 'number',
            'boolean' => 'checkbox',
            'date' => 'date',
            default => 'text',
        };
    }

    /** Valid calendar date `YYYY-MM-DD` (a leading date of a longer ISO string is accepted); '' otherwise. */
    public static function date(string $raw): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:$|[T ])/', $raw, $m) !== 1) {
            return '';
        }
        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? "{$m[1]}-{$m[2]}-{$m[3]}" : '';
    }

    private static function url(string $raw): string
    {
        if (function_exists('esc_url_raw')) {
            return esc_url_raw($raw);
        }
        return filter_var($raw, FILTER_VALIDATE_URL) ? $raw : '';
    }

    /** Decimal with max 2 places, comma accepted; non-negative unless $signed (money); '' when empty/invalid. */
    public static function number(string $raw, bool $signed = false): string
    {
        $raw = str_replace(',', '.', $raw);
        if ($raw === '' || !is_numeric($raw)) {
            return '';
        }
        $n = round((float) $raw, 2);
        return (string) ($signed ? $n : max(0, $n));
    }
}
