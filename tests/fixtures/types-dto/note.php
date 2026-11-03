<?php

// Module whose `context` callable receives the Core Post DTO (TypesTest::testContextCallableReceivesThePostDto).
return [
    'name' => 'note',
    'args' => ['public' => true],
    'fields' => ['mood' => ['type' => 'string', 'label' => 'Mood']],
    'context' => static function (array $data, WPFasty\Core\Model\Post $post, WPFasty\Types\PostType $type): array {
        $data['seen'] = [$post::class, $post->id, $post->kind, $post->slug, $type->name, $data['fields']['mood']];

        return $data;
    },
];
