<?php
// Configuration File
return [
    'app' => [
        'name' => 'Infinity API Client Panel',
        'url' => 'https://visionmall.fun',
        'env' => 'production', // 'local' or 'production'
        'debug' => true,
    ],
    'database' => [
        'host' => 'infapi',
        'port' => 3306,
        'database' => 'infapi', // Placeholder
        'username' => 'infapi',
        'password' => 'infapi',
        'charset' => 'utf8mb4',
    ],
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'prefix' => 'inf_api_',
    ],
    'security' => [
        'salt' => 'random_salt_string_here',
    ],
    'game_provider' => [
        'base_url' => 'https://igamingapis.live/api/v1',
        'token' => '7dc0a0dd5089de9ac605d5daef88411f',
        'secret' => 'df233e566223d2f458c3f631ba5c68eb',
    ],
];
