<?php
/**
 * Archetype fragment: callables (view model) appended to the generated types/project.php by theme:generate.
 * Only the entries between `return [` and `];` are used; {{theme.textDomain}} is substituted.
 *
 * @package Latte
 */

declare(strict_types=1);

return [
    /**
     * View-model: `project` (non-empty fields with labels) and `types`.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    'context' => static function (array $data, WP_Post $post, WPFasty\Types\PostType $type): array {
        $data['project'] = [];
        foreach ($type->fields as $key => $field) {
            if (($data['fields'][$key] ?? '') !== '') {
                $data['project'][$key] = ['label' => $field->label, 'value' => $data['fields'][$key]];
            }
        }
        $data['types'] = $data['terms']['project_type'] ?? [];
        return $data;
    },
];
