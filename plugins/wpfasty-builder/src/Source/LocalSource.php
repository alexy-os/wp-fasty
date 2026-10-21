<?php

declare(strict_types=1);

namespace WPFastyBuilder\Source;

/** A source in a folder: <dir>/catalog.json, <dir>/latte/{blocks,ui} and optionally <dir>/twig/{blocks,ui}. */
final class LocalSource implements SourceInterface
{
    /** @var array<string, mixed>|null */
    private ?array $catalog = null;

    public function __construct(
        private readonly string $id,
        private readonly string $dir,
        private readonly string $label = '',
        private readonly bool $sandboxed = false,
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

    public function catalog(): array
    {
        if ($this->catalog === null) {
            $file = $this->dir . '/catalog.json';
            $raw = is_file($file) ? (string) file_get_contents($file) : '';
            $data = $raw !== '' ? json_decode($raw, true) : null;
            $this->catalog = is_array($data) ? $data : ['blocks' => [], 'collections' => []];
        }
        return $this->catalog;
    }

    public function templateRoot(): string
    {
        return $this->dir . '/latte';
    }

    public function twigRoot(): ?string
    {
        return is_dir($this->dir . '/twig/blocks') ? $this->dir . '/twig' : null;
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
