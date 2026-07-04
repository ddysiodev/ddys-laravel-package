<?php

return [
    'api_base_url' => env('DDYS_API_BASE_URL', 'https://ddys.io/api/v1'),
    'site_base_url' => env('DDYS_SITE_BASE_URL', 'https://ddys.io'),
    'api_key' => env('DDYS_API_KEY', ''),
    'timeout' => (int) env('DDYS_TIMEOUT', 12),
    'retry_times' => (int) env('DDYS_RETRY_TIMES', 1),
    'retry_sleep' => (int) env('DDYS_RETRY_SLEEP', 150),
    'user_agent' => env('DDYS_USER_AGENT', 'ddys-laravel-package/0.1.0'),

    'routes' => [
        'enabled' => env('DDYS_ROUTES_ENABLED', true),
        'middleware' => ['web'],
        'prefix' => env('DDYS_ROUTE_PREFIX', 'ddys'),
        'name' => 'ddys.',
    ],

    'proxy' => [
        'enabled' => env('DDYS_PROXY_ENABLED', true),
        'middleware' => ['web'],
        'prefix' => env('DDYS_PROXY_PREFIX', 'ddys-api'),
        'name' => 'ddys.proxy.',
        'allow_routes' => [
            'movies', 'latest', 'hot', 'search', 'suggest', 'calendar',
            'movie', 'sources', 'related', 'comments',
            'collections', 'collection', 'shares', 'share',
            'requests', 'activities', 'user', 'types', 'genres', 'regions',
        ],
    ],

    'diagnostics' => [
        'enabled' => env('DDYS_DIAGNOSTICS_ENABLED', false),
        'middleware' => ['web'],
    ],

    'cache' => [
        'store' => env('DDYS_CACHE_STORE', null),
        'prefix' => 'ddys_laravel',
        'tags' => true,
        'default_ttl' => 300,
        'dictionary_ttl' => 86400,
        'fresh_ttl' => 300,
        'list_ttl' => 600,
        'detail_ttl' => 1800,
        'community_ttl' => 120,
    ],

    'display' => [
        'load_assets' => true,
        'show_nav' => true,
        'theme' => 'auto',
        'layout' => 'grid',
        'columns' => 4,
        'target' => '_blank',
        'default_limit' => 12,
        'show_source_link' => true,
        'show_poster' => true,
        'show_rating' => true,
        'show_summary' => true,
    ],

    'request_form' => [
        'enabled' => env('DDYS_REQUEST_FORM_ENABLED', false),
        'rate_limit_seconds' => 60,
        'honeypot_field' => 'ddys_website',
    ],

    'security' => [
        'max_limit' => 50,
        'max_per_page' => 50,
        'max_page' => 999,
        'allowed_resource_protocols' => ['http:', 'https:', 'magnet:', 'ed2k:', 'thunder:'],
    ],
];

