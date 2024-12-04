<?php
namespace WPFasty\Core;

class Container {
    private $bindings = [];
    private $instances = [];

    public function bind(string $abstract, $concrete): void {
        $this->bindings[$abstract] = $concrete;
    }

    public function singleton(string $abstract, $concrete): void {
        $this->bind($abstract, function($container) use ($concrete) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $concrete($container);
            }
            return $this->instances[$abstract];
        });
    }

    public function get(string $abstract) {
        if (!isset($this->bindings[$abstract])) {
            throw new \Exception("No binding found for {$abstract}");
        }

        $concrete = $this->bindings[$abstract];

        if ($concrete instanceof \Closure) {
            return $concrete($this);
        }

        return $concrete;
    }
} 