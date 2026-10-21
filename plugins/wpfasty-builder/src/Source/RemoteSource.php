<?php

declare(strict_types=1);

namespace WPFastyBuilder\Source;

use RuntimeException;
use WPFastyBuilder\Catalog;
use ZipArchive;

/**
 * A registry of ONE runtime published at a URL (docs/agent/builder-registry.md):
 * - format 2: <url>/catalog.json (`runtime`, `archive` {file, sha256}) + <url>/blocks.zip (blocks/, ui/ of the
 *   runtime);
 * - format 1 (legacy): <url>/catalog.json + <url>/latte.zip and/or <url>/twig.zip — only the zip of the site runtime is
 *   used, and it is required.
 * import() validates and stores the site runtime's copy under <dir> (format 2 layout); after that it reads like a
 * LocalSource. A registry of the other runtime is rejected — there is no substitute. Only that runtime's templates,
 * .json and .md files are extracted, and the templates always render sandboxed (see BlockRenderer).
 */
final class RemoteSource implements SourceInterface
{
    private const MAX_BYTES = 20 * 1024 * 1024;

    private ?LocalSource $local = null;

    /** @param callable(string): string $fetch downloads a URL and returns the body, throws on failure */
    public function __construct(
        private readonly string $id,
        private readonly string $url,
        private readonly string $dir,
        private readonly string $runtime,
        private $fetch,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->local()->label();
    }

    public function runtime(): string
    {
        return $this->runtime;
    }

    public function catalog(): array
    {
        return $this->local()->catalog();
    }

    public function templateRoot(): string
    {
        return $this->local()->templateRoot();
    }

    public function sandboxed(): bool
    {
        return true;
    }

    public function url(): string
    {
        return $this->url;
    }

    /** true when a copy of this runtime is stored (format 2, or a format-1 copy that holds this runtime's folder) */
    public function imported(): bool
    {
        if ($this->legacy()) {
            return is_dir($this->dir . '/' . $this->runtime . '/blocks');
        }
        $c = $this->stored();
        return $c !== null && Catalog::runtimeOf($c) === $this->runtime && is_dir($this->dir . '/blocks');
    }

    /**
     * Downloads the registry of this runtime, validates it and replaces the local copy.
     *
     * @return array{blocks: int, collections: int, runtime: string, version: string}
     */
    public function import(): array
    {
        $base = rtrim($this->url, '/');
        $raw = ($this->fetch)($base . '/catalog.json');
        $catalog = json_decode($raw, true);
        if (!is_array($catalog)) {
            throw new RuntimeException('catalog.json: not a JSON object');
        }
        $format = (int) ($catalog['format'] ?? 1);
        $runtime = Catalog::runtimeOf($catalog);
        if ($format >= 2 && $runtime !== $this->runtime) {
            throw new RuntimeException(sprintf(
                'This registry serves %s templates, the site renders %s: import the %s registry instead.',
                (string) ($runtime ?? 'unknown'),
                $this->runtime,
                $this->runtime,
            ));
        }
        $errors = Catalog::check($catalog);
        if ($errors !== []) {
            throw new RuntimeException('catalog.json: ' . implode('; ', array_slice($errors, 0, 5)));
        }
        if ($format >= 2) {
            $archive = (array) ($catalog['archive'] ?? []);
            $file = (string) ($archive['file'] ?? 'blocks.zip');
            $sha = (string) ($archive['sha256'] ?? '');
            if (preg_match('/^[a-z0-9][a-z0-9_-]*\.zip$/', $file) !== 1 || preg_match('/^[0-9a-f]{64}$/', $sha) !== 1) {
                throw new RuntimeException('catalog.json: archive needs a file name and a sha256');
            }
            $zip = ($this->fetch)($base . '/' . $file);
            if (!hash_equals($sha, hash('sha256', $zip))) {
                throw new RuntimeException($file . ' does not match the sha256 of catalog.json');
            }
        } else {
            $file = $this->runtime . '.zip';
            try {
                $zip = ($this->fetch)($base . '/' . $file);
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf(
                    'This registry has no %s package (%s): %s',
                    $this->runtime,
                    $file,
                    $e->getMessage(),
                ));
            }
            $catalog = Catalog::forRuntime($catalog, $this->runtime);
        }
        if ($zip === '' || strlen($zip) > self::MAX_BYTES) {
            throw new RuntimeException($file . ' is empty or larger than 20 MB');
        }
        $tmp = $this->dir . '.tmp';
        self::rmrf($tmp);
        if (!mkdir($tmp, 0775, true)) {
            throw new RuntimeException('cannot create ' . $tmp);
        }
        file_put_contents($tmp . '/.archive.zip', $zip);
        try {
            self::extract($tmp . '/.archive.zip', $tmp, $this->runtime);
        } finally {
            @unlink($tmp . '/.archive.zip');
        }
        $missing = Catalog::missingTemplates($catalog, $tmp);
        if (!is_dir($tmp . '/blocks') || $missing !== []) {
            self::rmrf($tmp);
            throw new RuntimeException(sprintf(
                '%s lacks %d %s template(s) of its catalog, e.g. %s',
                $file,
                count($missing),
                $this->runtime,
                (string) ($missing[0] ?? 'blocks/'),
            ));
        }
        $json = (string) json_encode($catalog, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($tmp . '/catalog.json', $json);
        self::rmrf($this->dir);
        rename($tmp, $this->dir);
        $this->local = null;
        return [
            'blocks' => count((array) ($catalog['blocks'] ?? [])),
            'collections' => count((array) ($catalog['collections'] ?? [])),
            'runtime' => $this->runtime,
            'version' => (string) ($catalog['version'] ?? ''),
        ];
    }

    /**
     * Extracts only safe relative paths: the runtime's templates, .json and .md (a leading "<runtime>/" folder of
     * format-1 zips is dropped). Templates of the other runtime and anything else (PHP, …) are skipped.
     */
    public static function extract(string $zipFile, string $to, string $runtime): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('the ' . $runtime . ' package is not a zip archive');
        }
        $allowed = ['json', 'md', $runtime === 'twig' ? 'twig' : 'latte'];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $name = preg_replace('#^' . preg_quote($runtime, '#') . '/#', '', str_replace('\\', '/', $name)) ?? '';
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $safe = preg_match('#^[A-Za-z0-9_\-]+(/[A-Za-z0-9_\-.]+)*$#', $name) === 1 && !str_contains($name, '..');
            if (!$safe || !in_array($ext, $allowed, true) || $name === 'catalog.json') {
                continue;
            }
            $target = $to . '/' . $name;
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0775, true);
            }
            file_put_contents($target, (string) $zip->getFromIndex($i));
        }
        $zip->close();
    }

    private function local(): LocalSource
    {
        if ($this->local === null) {
            $legacy = $this->legacy();
            $root = $legacy ? $this->dir . '/' . $this->runtime : $this->dir;
            $catalog = $legacy ? $this->dir . '/catalog.json' : null;
            $this->local = new LocalSource($this->id, $root, $this->runtime, '', true, $catalog);
        }
        return $this->local;
    }

    /** a copy stored by the format-1 importer (<dir>/latte, <dir>/twig next to one catalog.json) */
    private function legacy(): bool
    {
        $c = $this->stored();
        return $c !== null && (int) ($c['format'] ?? 1) < 2;
    }

    /** @return array<string, mixed>|null */
    private function stored(): ?array
    {
        $file = $this->dir . '/catalog.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        return is_array($data) ? $data : null;
    }

    private static function rmrf(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }
        if (!is_dir($path)) {
            return;
        }
        foreach ((array) scandir($path) as $f) {
            if ($f !== '.' && $f !== '..') {
                self::rmrf($path . '/' . $f);
            }
        }
        rmdir($path);
    }
}
