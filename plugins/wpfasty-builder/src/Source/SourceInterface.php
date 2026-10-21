<?php

declare(strict_types=1);

namespace WPFastyBuilder\Source;

/**
 * A block source of ONE runtime: a catalog (wpfasty-catalog@2, docs/agent/builder-registry.md) and the folder with
 * that runtime's blocks/ and ui/ templates. A local path and a remote registry URL give the same data shape. A source
 * never serves the other runtime's templates.
 */
interface SourceInterface
{
    /** short id (IDENT: a-z, 0-9, _ and -) */
    public function id(): string;

    public function label(): string;

    /** `latte` or `twig` */
    public function runtime(): string;

    /**
     * The decoded catalog.json of this runtime.
     *
     * @return array<string, mixed>
     */
    public function catalog(): array;

    /** absolute path of the runtime folder with blocks/ and ui/ */
    public function templateRoot(): string;

    /** true when the templates come from outside this plugin and must render sandboxed (Latte guard / Twig sandbox) */
    public function sandboxed(): bool;
}
