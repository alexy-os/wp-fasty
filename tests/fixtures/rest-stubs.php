<?php

declare(strict_types=1);

// Minimal WordPress REST stubs for WPFastyBuilder\Rest::renderBlock (tests/FragmentTest.php): request, response,
// error, capabilities ($GLOBALS['wp_test_caps']), route registry ($GLOBALS['wp_test_routes']).

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        /** @param array<string, mixed> $data */
        public function __construct(public string $code = '', public string $message = '', public array $data = [])
        {
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        /** @return array<string, mixed> */
        public function get_error_data(): array
        {
            return $this->data;
        }
    }
}
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        public function __construct(public mixed $data = null, public int $status = 200)
        {
        }

        public function get_data(): mixed
        {
            return $this->data;
        }
    }
}
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request implements ArrayAccess
    {
        /** @param array<string, mixed> $json */
        public function __construct(private array $json = [], private array $params = [])
        {
        }

        /** @return array<string, mixed> */
        public function get_json_params(): array
        {
            return $this->json;
        }

        public function get_param(string $k): mixed
        {
            return $this->params[$k] ?? $this->json[$k] ?? null;
        }

        public function offsetExists(mixed $k): bool
        {
            return $this->get_param((string) $k) !== null;
        }

        public function offsetGet(mixed $k): mixed
        {
            return $this->get_param((string) $k);
        }

        public function offsetSet(mixed $k, mixed $v): void
        {
            $this->params[(string) $k] = $v;
        }

        public function offsetUnset(mixed $k): void
        {
            unset($this->params[(string) $k]);
        }
    }
}
if (!function_exists('current_user_can')) {
    function current_user_can(string $cap, mixed ...$args): bool
    {
        $caps = $GLOBALS['wp_test_caps'] ?? [];
        return in_array($cap . ($args !== [] ? ':' . implode(',', $args) : ''), $caps, true) || in_array($cap, $caps, true);
    }
}
if (!function_exists('sanitize_key')) {
    function sanitize_key(string $key): string
    {
        return (string) preg_replace('/[^a-z0-9_\-]/', '', strtolower($key));
    }
}
if (!function_exists('taxonomy_exists')) {
    function taxonomy_exists(string $tax): bool
    {
        return false;
    }
}
if (!function_exists('get_post_type_object')) {
    function get_post_type_object(string $type): ?object
    {
        return null;
    }
}
if (!function_exists('get_taxonomy')) {
    function get_taxonomy(string $tax): object|false
    {
        return false;
    }
}
if (!function_exists('wp_upload_dir')) {
    /** @return array{basedir: string} */
    function wp_upload_dir(mixed $time = null, bool $create = true): array
    {
        return ['basedir' => sys_get_temp_dir() . '/wpfb-rest-test-uploads'];
    }
}
if (!function_exists('register_rest_route')) {
    /** @param array<mixed> $args */
    function register_rest_route(string $ns, string $route, array $args): bool
    {
        $GLOBALS['wp_test_routes'][$ns . $route] = $args;
        return true;
    }
}
