<?php

declare(strict_types=1);

namespace WPFastyBuilder\Source;

use WPFastyBuilder\Catalog;

/**
 * A source in a folder of one runtime: <dir>/catalog.json and <dir>/{blocks,ui} (e.g. sources/ui8kit/twig). A
 * format-1 catalog is read as the format-2 catalog of $runtime (Catalog::forRuntime).
 */
final class LocalSource implements SourceInterface
{
    /** @var array<string, mixed>|null */
    private ?array $catalog = null;

    public function __construct(
        private readonly string $id,
        private readonly string $dir,
        private readonly string $runtime,
        private readonly string $label = '',
        private readonly bool $sandboxed = false,
        private readonly ?string $catalogFile = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->label !== '' ? $this->label : (string) ($this->catalog()['label'] ?? $this->id);
    }

    public function runtime(): string
    {
        return $this->runtime;
    }

    public function catalog(): array
    {
        if ($this->catalog === null) {
            $file = $this->catalogFile ?? $this->dir . '/catalog.json';
            $raw = is_file($file) ? (string) file_get_contents($file) : '';
            $data = $raw !== '' ? json_decode($raw, true) : null;
            $data = is_array($data) ? Catalog::forRuntime($data, $this->runtime) : null;
            $ok = $data !== null && Catalog::runtimeOf($data) === $this->runtime;
            $this->catalog = $ok ? $data : ['runtime' => $this->runtime, 'blocks' => [], 'collections' => []];
        }
        return $this->catalog;
    }

    public function templateRoot(): string
    {
        return $this->dir;
    }

    public function sandboxed(): bool
    {
        return $this->sandboxed;
    }

    public function dir(): string
    {
        return $this->dir;
    }
}
