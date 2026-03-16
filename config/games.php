<?php
// Config for Games with provider API credentials
return [
    'providers' => [
        'default' => [
            // Provider credentials (keep these server-side only)
            'api_token' => '408152b61968cb99ba4f3e2b2625dcc2',
            // Provider doc: "Must be 32 bytes". This value is 32 ASCII chars (32 bytes).
            'api_secret' => 'd0c96580a5f2cd85a52f2d1e01d72670',
            'server_url' => 'https://igamingapis.live/api/v1',
            // Defaults used if client doesn't pass return/callback
            'callback_url' => 'https://visionmall.fun/v1/callback/',
            'return_url' => 'https://visionmall.fun/v1/return/',
        ],
    ],
    // Mock Game List
    'games' => [
        ['uid' => '3978', 'name' => 'Sweet Bonanza', 'provider' => 'Pragmatic Play', 'img' => 'https://via.placeholder.com/300x200/ef4444/ffffff?text=Sweet+Bonanza', 'rtp' => '96.5%'],
        ['uid' => '4001', 'name' => 'Gates of Olympus', 'provider' => 'Pragmatic Play', 'img' => 'https://via.placeholder.com/300x200/8b5cf6/ffffff?text=Gates+of+Olympus', 'rtp' => '96.5%'],
        ['uid' => '4022', 'name' => 'Sugar Rush', 'provider' => 'Pragmatic Play', 'img' => 'https://via.placeholder.com/300x200/ec4899/ffffff?text=Sugar+Rush', 'rtp' => '96.6%'],
        ['uid' => '5010', 'name' => 'Big Bass Splash', 'provider' => 'Pragmatic Play', 'img' => 'https://via.placeholder.com/300x200/10b981/ffffff?text=Big+Bass', 'rtp' => '96.7%'],
    ]
];
