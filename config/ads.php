<?php

use App\Enums\AdCampaignGoal;

return [
    'goals' => [
        AdCampaignGoal::WebsiteTraffic->value => [
            'label' => 'Website Traffic',
            'description' => 'Send customers to your website.',
            'ctas' => ['LEARN_MORE', 'SHOP_NOW', 'CONTACT_US', 'SIGN_UP'],
        ],
        AdCampaignGoal::LeadGeneration->value => [
            'label' => 'Lead Generation',
            'description' => 'Collect customer details using a Meta instant form.',
            'ctas' => ['SIGN_UP', 'GET_QUOTE', 'APPLY_NOW', 'LEARN_MORE'],
        ],
        AdCampaignGoal::WhatsAppMessages->value => [
            'label' => 'WhatsApp Messages',
            'description' => 'Encourage customers to start a WhatsApp conversation.',
            'ctas' => ['WHATSAPP_MESSAGE', 'CONTACT_US'],
        ],
    ],
    'media' => [
        'single_image' => ['extensions' => ['jpg', 'jpeg', 'png', 'webp'], 'mimes' => ['image/jpeg', 'image/png', 'image/webp'], 'max_kb' => 10240],
        'single_video' => ['extensions' => ['mp4', 'mov', 'webm'], 'mimes' => ['video/mp4', 'video/quicktime', 'video/webm'], 'max_kb' => 102400],
    ],
    // These are product-level draft checks, not Meta's final minimums.
    'minimum_budget' => ['USD' => 5, 'INR' => 100, 'GBP' => 5, 'EUR' => 5, 'default' => 5],
    'radius' => ['min' => 1, 'max' => 80],
    'genders' => ['all', 'male', 'female'],
];
