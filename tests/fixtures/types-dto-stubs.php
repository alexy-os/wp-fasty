<?php

declare(strict_types=1);

// get_post_meta / get_the_terms for Registry::postData() (TypesTest only).
if (!function_exists('get_post_meta')) {
    function get_post_meta(int $id, string $key = '', bool $single = false): mixed
    {
        return '_note_mood' === $key ? 'calm' : '';
    }
}
if (!function_exists('get_the_terms')) {
    function get_the_terms(int $id, string $taxonomy): array|false
    {
        return false;
    }
}
