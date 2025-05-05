<?php

declare(strict_types=1);

namespace WPFasty\Data;

/**
 * Context collection for template rendering
 */
final class ContextCollection implements \ArrayAccess, \IteratorAggregate, \JsonSerializable
{
    /**
     * Data storage
     */
    private array $data = [];
    
    /**
     * Add data object to context
     */
    public function add(string $key, DataObject|array $value): self
    {
        if ($value instanceof DataObject) {
            $this->data[$key] = $value->toArray();
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Get data by key
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->data;
    }
    
    /**
     * Convert nested DataObjects in arrays
     */
    private function convertArrayDataObjects(array $array): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            if ($value instanceof DataObject) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = $this->convertArrayDataObjects($value);
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }
    
    // IteratorAggregate implementation
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->data);
    }
    
    // ArrayAccess implementation
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }
    
    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }
    
    public function offsetSet($offset, $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            if ($value instanceof DataObject) {
                $this->data[$offset] = $value->toArray();
            } else {
                $this->data[$offset] = $value;
            }
        }
    }
    
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }
    
    // JsonSerializable implementation
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
