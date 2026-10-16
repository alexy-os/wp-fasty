<?php
/**
 * One declarative post-type module (`types/<name>.php`).
 *
 * @package Latte
 */

declare(strict_types=1);

namespace WPFasty\Types;

final class PostType
{
    /** @var array<string, Field> */
    public readonly array $fields;

    /**
     * @param array<string, mixed> $def Module definition array.
     */
    public function __construct(public readonly string $name, public readonly array $def)
    {
        $prefix = (string) ($def['meta_prefix'] ?? $name);
        $fields = [];
        foreach ((array) ($def['fields'] ?? []) as $key => $field) {
            $fields[(string) $key] = Field::fromArray((string) $key, (array) $field, $prefix);
        }
        $this->fields = $fields;
    }

    /**
     * @param array<string, mixed> $def
     */
    public static function fromArray(array $def): self
    {
        $name = (string) ($def['name'] ?? '');
        if (!preg_match('/^[a-z][a-z0-9_]{1,19}$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Invalid post type name "%s"', $name));
        }
        return new self($name, $def);
    }

    /** @return array<string, mixed> */
    public function args(): array
    {
        return (array) ($this->def['args'] ?? []);
    }

    /** @return array<string, array<string, mixed>> */
    public function taxonomies(): array
    {
        return (array) ($this->def['taxonomies'] ?? []);
    }

    public function supports(string $capability): bool
    {
        $supports = (array) ($this->def['supports'] ?? []);
        return in_array($capability, $supports, true) || array_key_exists($capability, $supports);
    }

    /** @return array<string, mixed> */
    public function capability(string $capability): array
    {
        $value = ((array) ($this->def['supports'] ?? []))[$capability] ?? [];
        return is_array($value) ? $value : [];
    }

    public function view(string $kind): ?string
    {
        $view = ((array) ($this->def['views'] ?? []))[$kind] ?? null;
        return is_string($view) && $view !== '' ? $view : null;
    }

    /** @return array<string, mixed> */
    public function archive(): array
    {
        return (array) ($this->def['archive'] ?? []);
    }

    /** @return array<string, mixed>|false */
    public function metabox(): array|false
    {
        $box = $this->def['metabox'] ?? [];
        return $box === false ? false : (array) $box;
    }

    public function textDomain(): string
    {
        return (string) ($this->def['text_domain'] ?? 'latte');
    }
}
