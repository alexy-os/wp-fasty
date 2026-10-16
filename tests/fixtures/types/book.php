<?php

return [
    'name' => 'book',
    'meta_prefix' => 'lib',
    'args' => ['public' => true],
    'taxonomies' => ['genre' => ['hierarchical' => true]],
    'fields' => [
        'isbn' => ['type' => 'string', 'label' => 'ISBN'],
        'price' => ['type' => 'money', 'label' => 'Price'],
        'pages' => ['type' => 'integer', 'label' => 'Pages'],
        'site' => ['type' => 'url', 'label' => 'Site'],
        'signed' => ['type' => 'boolean', 'label' => 'Signed'],
    ],
    'views' => ['single' => 'pages/book', 'archive' => 'pages/books'],
    'supports' => ['purchasable' => ['price_field' => 'price']],
];
