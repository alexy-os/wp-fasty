<?php

declare(strict_types=1);

namespace WPFastyBuilder\Source;

use RuntimeException;
use WPFastyBuilder\Catalog;
use ZipArchive;

/**
 * A source published at a URL: <url>/catalog.json and <url>/latte.zip (the latte/ folder of a local source, zipped).
 * import() downloads both into a local folder; after that it reads like a LocalSource. Only .latte, .json and .md
 * files are extracted, and the templates always render in the Latte sandbox (see BlockRenderer).
 */
final class RemoteSource implements SourceInterface
{
    private const MAX_BYTES = 20 * 1024 * 1024;
    private const ALLOWED = ['latte', 'json', 'md'];

    private LocalSource $local;

    /** @param callable(string): string $fetch downloads a URL and returns the body, throws on failure */
    public function __construct(
        private readonly string $id,
        private readonly string $url,
        private readonly string $dir,
        private $fetch,
    ) {
        $this->local = new LocalSource($id, $dir, '', true);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function label(): string
    {
        return $this->local->label();
    }

    public function catalog(): array
    {
        return $this->local->catalog();
    }

    public function templateRoot(): string
    {
        return $this->local->templateRoot();
    }

    public function sandboxed(): bool
    {
        return true;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function imported(): bool
    {
        return is_file($this->dir . '/catalog.json') && is_dir($this->dir . '/latte/blocks');
    }

    /**
     * Downloads catalog.json and latte.zip, validates them and replaces the local copy.
     *
     * @return array{blocks: int, collections: int}
     */
    public function import(): array
    {
        $base = rtrim($this->url, '/');
        $raw = ($this->fetch)($base . '/catalog.json');
        $catalog = json_decode($raw, true);
        $errors = Catalog::check(is_array($catalog) ? $catalog : null);
        if ($errors !== []) {
            throw new RuntimeException('catalog.json: ' . implode('; ', array_slice($errors, 0, 5)));
        }
        $zip = ($this->fetch)($base . '/latte.zip');
        if ($zip === '' || strlen($zip) > self::MAX_BYTES) {
            throw new RuntimeException('latte.zip is empty or larger than 20 MB');
        }
        $tmp = $this->dir . '.tmp';
        self::rmrf($tmp);
        if (!mkdir($tmp . '/latte', 0775, true)) {
            throw new RuntimeException('cannot create ' . $tmp);
        }
        $zipFile = $tmp . '/latte.zip';
        file_put_contents($zipFile, $zip);
        self::extract($zipFile, $tmp . '/latte');
        unlink($zipFile);
        if (!is_dir($tmp . '/latte/blocks')) {
            self::rmrf($tmp);
            throw new RuntimeException('latte.zip has no blocks/ folder');
        }
        file_put_contents($tmp . '/catalog.json', $raw);
        self::rmrf($this->dir);
        rename($tmp, $this->dir);
        $this->local = new LocalSource($this->id, $this->dir, '', true);
        return [
            'blocks' => count((array) ($catalog['blocks'] ?? [])),
            'collections' => count((array) ($catalog['collections'] ?? [])),
        ];
    }

    /** extracts only safe relative paths with allowed extensions */
    public static function extract(string $zipFile, string $to): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) {
            throw new RuntimeException('latte.zip is not a zip archive');
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string) $zip->getNameIndex($i);
            $name = preg_replace('#^latte/#', '', str_replace('\\', '/', $name)) ?? '';
            if ($name === '' || str_ends_with($name, '/')) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $safe = preg_match('#^[A-Za-z0-9_\-]+(/[A-Za-z0-9_\-.]+)*$#', $name) === 1 && !str_contains($name, '..');
            if (!$safe || !in_array($ext, self::ALLOWED, true)) {
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
