<?php

declare(strict_types=1);

namespace WPFasty\Core;

interface ContainerInterface
{
    public const TAG_BOOTABLE = 'bootable';

    public function get(string $id): mixed;

    public function has(string $id): bool;

    /** @return list<string> */
    public function findTaggedServiceIds(string $tag): array;

    public function addTag(string $id, string $tag, array $attributes = []): void;
}
