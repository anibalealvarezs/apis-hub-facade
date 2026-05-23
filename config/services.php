<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_APP_ID'),
        'client_secret' => env('FACEBOOK_APP_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
        'channel_scopes' => [
            'marketing' => [
                'ads_read',
                'business_management',
            ],
            'organic' => [
                'pages_show_list',
                'pages_read_engagement',
                'pages_read_user_content',
                'read_insights',
                'instagram_basic',
                'instagram_manage_insights',
            ],
            'default' => [
                'public_profile',
                'email',
            ]
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'channel_scopes' => [
            'search_console' => [
                'https://www.googleapis.com/auth/webmasters.readonly',
            ],
            'analytics' => [
                'https://www.googleapis.com/auth/analytics.readonly',
            ],
            'ads' => [
                'https://www.googleapis.com/auth/adwords',
            ],
            'default' => [
                'https://www.googleapis.com/auth/userinfo.profile',
                'https://www.googleapis.com/auth/userinfo.email',
            ]
        ],
    ],

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'project_id' => env('RECAPTCHA_PROJECT_ID'),
        'api_key' => env('RECAPTCHA_ENTERPRISE_API_KEY'),
    ],

    'gtm' => [
        'id' => env('GTM_ID'),
    ],

];

