<?php

/**
 * One declarative post-type module (`types/<name>.php`).
 */

declare(strict_types=1);

namespace WPFasty\Types;

use WPFasty\Core\Cast;

final class PostType
{
    /** @var array<string, Field> */
    public readonly array $fields;

    /** @var lowercase-string&non-empty-string */
    public readonly string $name;

    /**
     * @param array<string, mixed> $def module definition array
     */
    public function __construct(string $name, public readonly array $def)
    {
        if (1 !== preg_match('/^[a-z][a-z0-9_]{1,19}$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Invalid post type name "%s"', $name));
        }
        $name = strtolower($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Empty post type name');
        }
        $this->name = $name;
        $prefix = Cast::string($def['meta_prefix'] ?? $name);
        $fields = [];
        foreach ((array) ($def['fields'] ?? []) as $key => $field) {
            $fields[(string) $key] = Field::fromArray((string) $key, Cast::map($field), $prefix);
        }
        $this->fields = $fields;
    }

    /**
     * @param array<string, mixed> $def
     */
    public static function fromArray(array $def): self
    {
        return new self(Cast::string($def['name'] ?? ''), $def);
    }

    /** @return array<string, mixed> */
    public function args(): array
    {
        return Cast::map($this->def['args'] ?? []);
    }

    /** @return array<string, array<string, mixed>> */
    public function taxonomies(): array
    {
        return array_map(Cast::map(...), Cast::map($this->def['taxonomies'] ?? []));
    }

    public function supports(string $capability): bool
    {
        $supports = (array) ($this->def['supports'] ?? []);

        return in_array($capability, $supports, true) || array_key_exists($capability, $supports);
    }

    /** @return array<string, mixed> */
    public function capability(string $capability): array
    {
        return Cast::map(Cast::map($this->def['supports'] ?? [])[$capability] ?? []);
    }

    public function view(string $kind): ?string
    {
        $view = ((array) ($this->def['views'] ?? []))[$kind] ?? null;

        return is_string($view) && '' !== $view ? $view : null;
    }

    /** @return array<string, mixed> */
    public function archive(): array
    {
        return Cast::map($this->def['archive'] ?? []);
    }

    /** @return array<string, mixed>|false */
    public function metabox(): array|false
    {
        $box = $this->def['metabox'] ?? [];

        return false === $box ? false : Cast::map($box);
    }

    public function textDomain(): string
    {
        return Cast::string($this->def['text_domain'] ?? 'wp-fasty');
    }
}
