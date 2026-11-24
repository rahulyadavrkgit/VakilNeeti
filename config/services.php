
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more.
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

    'otp_bypass_enabled' => env('OTP_BYPASS_ENABLED', false),

    // Numbers allowed to skip the SMS code while the bypass is on (comma separated; "*" = every number,
    // only for a private development machine). Empty means nobody skips it.
    'otp_bypass_numbers' => array_values(array_filter(array_map('trim', explode(',', (string) env('OTP_BYPASS_NUMBERS', ''))))),

    // Store listings for the VakilNiti phone apps. Until these are set, the site offers
    // "Add to Home Screen" installation instead of store badges.
    'mobile_apps' => [
        'play_store' => env('PLAY_STORE_URL'),
        'app_store' => env('APP_STORE_URL'),
    ],

    'twilio' => [

        // Existing ordinary SMS configuration
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),

        // Twilio Verify configuration
        'api_key' => env('TWILIO_API_KEY'),
        'api_secret' => env('TWILIO_API_SECRET'),
        'verify_service_sid' => env('TWILIO_VERIFY_SERVICE_SID'),

    ],

];
