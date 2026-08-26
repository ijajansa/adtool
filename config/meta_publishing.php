<?php

return [
    'contract_version' => 'v26.0',
    'required_permissions' => ['ads_management', 'pages_manage_ads'],
    'paused_status' => 'PAUSED',
    'goals' => [
        'website_traffic' => [
            'objective' => 'OUTCOME_TRAFFIC',
            'optimization_goal' => 'LANDING_PAGE_VIEWS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => 'WEBSITE',
        ],
        'lead_generation' => [
            'objective' => 'OUTCOME_LEADS',
            'optimization_goal' => 'LEAD_GENERATION',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => 'ON_AD',
        ],
        'whatsapp_messages' => [
            'objective' => 'OUTCOME_ENGAGEMENT',
            'optimization_goal' => 'CONVERSATIONS',
            'billing_event' => 'IMPRESSIONS',
            'destination_type' => 'WHATSAPP',
        ],
    ],
    'special_ad_categories' => ['CREDIT', 'EMPLOYMENT', 'HOUSING', 'ISSUES_ELECTIONS_POLITICS'],
    'currency_precision' => [
        'USD' => 2, 'INR' => 2, 'EUR' => 2, 'GBP' => 2, 'AUD' => 2, 'CAD' => 2,
        'JPY' => 0, 'KRW' => 0, 'CLP' => 0, 'HUF' => 0, 'ISK' => 0, 'PYG' => 0,
    ],
    'minimum_schedule_lead_minutes' => 10,
    'password_confirmation_window_seconds' => 900,
    'video_poll_attempts' => 5,
    'video_poll_backoff_seconds' => [2, 5, 10, 20, 30],
];
