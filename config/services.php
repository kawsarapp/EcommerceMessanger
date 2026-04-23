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
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],

    'google_vision' => [
        'api_key' => env('GOOGLE_VISION_API_KEY'),
    ],

    'google_cloud' => [
        'project_id'       => env('GOOGLE_CLOUD_PROJECT_ID'),
        'location'         => env('GOOGLE_CLOUD_LOCATION', 'asia-east1'),
        'product_set_id'   => env('GOOGLE_VISION_PRODUCT_SET_ID'),
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        'storage_bucket'   => env('GOOGLE_CLOUD_STORAGE_BUCKET'),
    ],

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'http://127.0.0.1:3001'),
    ],

    'bdcourier' => [
        'api_key' => env('BDCOURIER_API_KEY'),
        'base_url' => 'https://api.bdcourier.com',
    ],

];
