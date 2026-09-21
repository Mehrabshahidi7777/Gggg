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
        | اختیاری. اگر برای یادآوری تمدید پترن ساخته‌اید، شناسه‌اش را
        | اینجا بگذارید. خالی بماند، پیامک به‌صورت متن ساده ارسال
        | می‌شود (نیازمند خط خدماتی).
        |
        | متغیرهای پترن آموت باید داخل %% و با حروف لاتین باشند:
        |
        |   %name% عزیز، اشتراک %plan% شما تا %days% روز دیگر به پایان
        |   می‌رسد. برای جلوگیری از تعلیق آگهی‌ها آن را تمدید کنید.
        |
        | ترتیب مقادیر: {نام کاربر} , {نوع اشتراک} , {تعداد روز}
        */
        'pattern_renewal_id' => env('AMOOT_PATTERN_RENEWAL_ID'),

        /*
        | پترنِ «اشتراک تمام شد» - جدا، چون متنش فرق دارد و نباید
        | بگوید «تا ۰ روز دیگر».
        |
        |   %name% عزیز، اشتراک %plan% شما به پایان رسید و آگهی‌هایتان
        |   تعلیق شد. تا شش ماه فرصت دارید با تمدید، آنها را بازگردانید.
        |
        | ترتیب مقادیر: {نام کاربر} , {نوع اشتراک}
        */
        'pattern_expired_id' => env('AMOOT_PATTERN_EXPIRED_ID'),
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'mode' => env('ZARINPAL_MODE', 'normal'),
    ],

];