<?php

declare(strict_types=1);

namespace WPFastyBuilder\Source;

/**
 * A block source: a catalog (catalog.json, see docs/catalog.md) and the folder of Latte templates it points to.
 * A local path and a remote registry URL give the same data shape.
 */
interface SourceInterface
{
    /** short id (IDENT: a-z, 0-9, _ and -) */
    public function id(): string;

    public function label(): string;

    /**
     * The decoded catalog.json.
     *
     * @return array<string, mixed>
     */
    public function catalog(): array;

    /** absolute path of the folder with blocks/ and ui/ templates */
    public function templateRoot(): string;

    /** absolute path of the generated Twig package (blocks/, ui/ as .html.twig), null when the source has none */
    public function twigRoot(): ?string;

    /** true when the templates come from outside this plugin and must render sandboxed (Latte guard / Twig sandbox) */
    public function sandboxed(): bool;
}
