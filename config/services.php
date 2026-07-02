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

    /*
    |--------------------------------------------------------------------------
    | Channex (channel manager)
    |--------------------------------------------------------------------------
    |
    | `fake` gates which ChannelManagerClient implementation is bound (see
    | AppServiceProvider::register()): true -> FakeChannexClient (in-memory, no network,
    | what every test uses), false -> the real ChannexClient (REST, requires api_key).
    | Defaults to true so this is never accidentally live without an explicit opt-out AND
    | real credentials — "config-gated" per docs/DECISIONS.md.
    |
    */
    'channex' => [
        'base_url' => env('CHANNEX_BASE_URL', 'https://staging.channex.io/api/v1'),
        'api_key' => env('CHANNEX_API_KEY'),
        'fake' => env('CHANNEX_FAKE', true),
    ],

];
