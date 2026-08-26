<?php

return [
    'app_id' => env('META_APP_ID'),
    'app_secret' => env('META_APP_SECRET'),
    'redirect_uri' => env('META_REDIRECT_URI'),
    'graph_version' => env('META_GRAPH_VERSION', 'v26.0'),
    'oauth_base_url' => rtrim(env('META_OAUTH_BASE_URL', 'https://www.facebook.com'), '/'),
    'graph_base_url' => rtrim(env('META_GRAPH_BASE_URL', 'https://graph.facebook.com'), '/'),
    'oauth_scopes' => [
        'ads_management',
        'ads_read',
        'business_management',
        'pages_show_list',
        'pages_read_engagement',
        // 'instagram_basic',
    ],
    'http_timeout' => (int) env('META_HTTP_TIMEOUT', 20),
    'connect_timeout' => (int) env('META_CONNECT_TIMEOUT', 8),
    'oauth_state_ttl' => 600,
    'expiry_warning_days' => 7,
];
