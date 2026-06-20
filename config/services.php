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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Google "Sign in with Google" (OAuth 2.0). Credentials and the on/off
    | toggle are operator-editable from Admin > Settings > Social login, which
    | overlays these values at runtime (see SettingsServiceProvider). The
    | redirect URI to register in Google Cloud Console is route('auth.google.callback').
    */
    'google' => [
        'enabled' => (bool) env('GOOGLE_LOGIN_ENABLED', false),
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

    /*
    | GitHub "Sign in with GitHub" (OAuth App). Callback to register in the GitHub
    | OAuth App settings is route('auth.github.callback'). Operator-editable from
    | Admin > Settings > Social login.
    */
    'github' => [
        'enabled' => (bool) env('GITHUB_LOGIN_ENABLED', false),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
    ],

    /*
    | Facebook Login. Valid OAuth redirect URI to register in the Meta app is
    | route('auth.facebook.callback'). Operator-editable from the same screen.
    */
    'facebook' => [
        'enabled' => (bool) env('FACEBOOK_LOGIN_ENABLED', false),
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    ],

];
