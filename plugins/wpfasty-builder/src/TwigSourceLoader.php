<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Sandbox\SourcePolicyInterface;
use Twig\Source;

/**
 * The Twig loader of a sandboxed (remote) source: blocks/ from the source's own root, the shared layers (compose/,
 * ui/, icons/; see SourceLoader) from the plugin's bundled package. As the source policy of the sandbox it sandboxes
 * every template except those shared trusted layers.
 */
final class TwigSourceLoader implements LoaderInterface, SourcePolicyInterface
{
    private FilesystemLoader $own;

    private FilesystemLoader $bundled;

    public function __construct(string $root, string $bundled)
    {
        $this->own = new FilesystemLoader([$root]);
        $this->bundled = new FilesystemLoader([$bundled]);
    }

    private function pick(string $name): FilesystemLoader
    {
        return SourceLoader::shared(ltrim($name, '/')) ? $this->bundled : $this->own;
    }

    public function getSourceContext(string $name): Source
    {
        return $this->pick($name)->getSourceContext($name);
    }

    public function getCacheKey(string $name): string
    {
        return $this->pick($name)->getCacheKey($name);
    }

    public function isFresh(string $name, int $time): bool
    {
        return $this->pick($name)->isFresh($name, $time);
    }

    public function exists(string $name): bool
    {
        return $this->pick($name)->exists($name);
    }

    public function enableSandbox(Source $source): bool
    {
        return !SourceLoader::shared(ltrim($source->getName(), '/'));
    }
}
