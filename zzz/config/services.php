<?php

return [

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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'amoot' => [
        'token' => env('AMOOT_TOKEN'),
        'line_number' => env('AMOOT_LINE_NUMBER'),
        'pattern_code_id' => env('AMOOT_PATTERN_CODE_ID'),

        /*
        | اختیاری. اگر برای «یادآوری تمدید اشتراک» یک پترن در پنل آموت
        | تعریف کرده‌اید، شناسه‌اش را اینجا بگذارید. خالی بماند، پیامک
        | به‌صورت متن ساده ارسال می‌شود (نیازمند خط خدماتی).
        |
        | ترتیب مقادیر پترن: {نام کاربر} ; {نوع اشتراک} ; {تعداد روز}
        */
        'pattern_renewal_id' => env('AMOOT_PATTERN_RENEWAL_ID'),
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'mode' => env('ZARINPAL_MODE', 'normal'),
    ],

];