<?php

declare(strict_types=1);

namespace WPFasty\Template;

use Latte\Loaders\FileLoader;

/**
 * Resolves templates from multiple roots (theme views first, then UI8Kit ui/).
 */
final class MultiRootLoader extends FileLoader
{
    /** @var list<string> */
    private array $roots;

    /** @param list<string> $roots Absolute directories; first match wins */
    public function __construct(array $roots)
    {
        $normalized = [];
        foreach ($roots as $root) {
            $path = rtrim(str_replace('\\', '/', $root), '/');
            if ($path !== '' && is_dir($path)) {
                $normalized[] = $path;
            }
        }
        if ($normalized === []) {
            throw new \RuntimeException('MultiRootLoader requires at least one directory');
        }
        $this->roots = $normalized;
        parent::__construct($normalized[0]);
    }

    public function getContent(string $fileName): string
    {
        $path = $this->find($fileName);
        if ($path === null) {
            throw new \RuntimeException("Missing template \"{$fileName}\"");
        }
        return (string) file_get_contents($path);
    }

    public function getReferredName(string $file, string $referringFile): string
    {
        if ($this->isAbsolute($file)) {
            return $file;
        }
        if ($referringFile !== '' && !$this->isAbsolute($referringFile)) {
            $ref = $this->find($referringFile);
            if ($ref !== null) {
                $candidate = dirname($ref) . '/' . $file;
                if (is_file($candidate)) {
                    return $this->toLogical($candidate) ?? $file;
                }
            }
        }
        return $file;
    }

    public function isExpired(string $file, int $time): bool
    {
        $path = $this->find($file);
        return $path === null || filemtime($path) > $time;
    }

    private function find(string $fileName): ?string
    {
        $name = ltrim(str_replace('\\', '/', $fileName), '/');
        if ($this->isAbsolute($name) && is_file($name)) {
            return $name;
        }
        foreach ($this->roots as $root) {
            $path = $root . '/' . $name;
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function isAbsolute(string $path): bool
    {
        return (bool) preg_match('#^([a-z]:)?/#i', str_replace('\\', '/', $path));
    }

    private function toLogical(string $absolute): ?string
    {
        $abs = str_replace('\\', '/', $absolute);
        foreach ($this->roots as $root) {
            if (str_starts_with($abs, $root . '/')) {
                return substr($abs, strlen($root) + 1);
            }
        }
        return null;
    }
}
