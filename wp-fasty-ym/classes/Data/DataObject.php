<?php

declare(strict_types=1);

namespace WPFasty\Data;

/**
 * Base immutable data object with serialization support
 */
abstract readonly class DataObject implements \JsonSerializable
{
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * Implement JsonSerializable
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
    
    /**
     * Create from array
     */
    public static function fromArray(array $data): static
    {
        return new static(...$data);
    }
}
