
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

    // Account emails allowed to skip the emailed code while the bypass is on (comma separated; "*" = everyone,
    // only for a private development machine). Empty means nobody skips it. Works only when APP_ENV=local.
    'otp_bypass_emails' => array_values(array_filter(array_map(fn (string $email): string => strtolower(trim($email)), explode(',', (string) env('OTP_BYPASS_EMAILS', ''))))),

    // Mobile numbers allowed to skip the SMS code on phone log-in while the bypass is on (same rules as above).
    'otp_bypass_numbers' => array_values(array_filter(array_map('trim', explode(',', (string) env('OTP_BYPASS_NUMBERS', ''))))),

    // Store listings for the VakilNiti phone apps. Until these are set, the site offers
    // "Add to Home Screen" installation instead of store badges.
    'mobile_apps' => [
        'play_store' => env('PLAY_STORE_URL'),
        'app_store' => env('APP_STORE_URL'),
    ],

    // Who hears about site errors in the bell each morning (comma-separated mobile numbers; admins always do).
    'alerts' => [
        'mobiles' => array_values(array_filter(array_map('trim', explode(',', (string) env('ERROR_ALERT_MOBILES', ''))))),
    ],

    // MSG91 email: sign-in and sign-up codes. The sending domain must be verified in MSG91, and the template
    // (its ID or name) must use the variable "otp" (optionally "name" and "minutes").
    'msg91' => [
        'key' => env('MSG91_AUTH_KEY'),
        'email_domain' => env('MSG91_EMAIL_DOMAIN'),
        'email_from' => env('MSG91_EMAIL_FROM'),
        'email_from_name' => env('MSG91_EMAIL_FROM_NAME', 'VakilNiti'),
        'otp_template_id' => env('MSG91_EMAIL_OTP_TEMPLATE'),
    ],

    // AuthKey.io (kept for phone sign-in later): the auth key and the SID of the DLT-approved code template ({#otp#}).
    'authkey' => [
        'key' => env('AUTHKEY_API_KEY'),
        'otp_sid' => env('AUTHKEY_OTP_SID'),
        'country_code' => env('AUTHKEY_COUNTRY_CODE', '91'),
    ],

];
