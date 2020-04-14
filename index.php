<?php

@include_once __DIR__ . '/vendor/autoload.php';

if (!class_exists('Bnomei\MemcachedPage')) {
    require_once __DIR__ . '/models/MemcachedPage.php';
}

Kirby::plugin('bnomei/page-memcached', [
    'options' => [
        'host'    => '127.0.0.1',
        'port'    => 11211,
        'prefix'  => 'page-memcached',
        'expire'  => 0,
    ],
]);
