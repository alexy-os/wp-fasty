<?php

declare(strict_types=1);

namespace WPFasty\Providers;

class QueryConfigurator
{
    protected array $query_args = [];

    public function __construct(array $query_args = [])
    {
        $this->query_args = $query_args;
    }

    public function setQueryArgs(array $query_args): self
    {
        $this->query_args = $query_args;
        return $this;
    }

    public function getQueryArgs(): array
    {
        return $this->query_args;
    }

    public function mergeConfig(array $customConfig): self
    {
        $this->query_args = array_merge($this->query_args, $customConfig);
        return $this;
    }

    public function updateConfig(string $key, $value): self
    {
        $this->query_args[$key] = $value;
        return $this;
    }
}