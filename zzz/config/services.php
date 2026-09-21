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
        | یک پترن برای هر سه مرحله کافی است. متغیرهای آموت باید داخل
        | %% و با حروف لاتین باشند:
        |
        |   اشتراک %plan% شما %state%.
        |   sazmat.com
        |
        | ترتیب مقادیر: {خدمات|محصولات} , {وضعیت}
        |
        | و %state% یکی از این‌هاست:
        |
        |   تا 7 روز دیگر تمام می‌شود
        |   فردا تمام می‌شود
        |   تمام شد؛ آگهی‌ها تعلیق شدند
        |
        | ⚠️ بدترین حالت ۶۴ کاراکتر است - یعنی یک صفحه‌ی پیامک فارسی
        | (سقف ۷۰). هر کلمه‌ای که به این متن اضافه شود، هزینه‌ی هر
        | یادآوری را دو برابر می‌کند.
        |
        | ⚠️ آن ۶ کاراکتر، «لغو11» است.
        |
        | آموت خودش آن را ته هر پیامک می‌چسباند و در شمارنده‌ی پنل
        | هم حساب می‌شود. در نسخه‌ی اول این متن، «تمدید: » هم بود و
        | جمعش با لغو11 می‌شد ۷۱ - یعنی دقیقاً یک کاراکتر بیرون از
        | یک صفحه، و دو برابر هزینه. «تمدید: » حذف شد؛ خودِ دامنه
        | همان را می‌رساند.
        */
        'pattern_renewal_id' => env('AMOOT_PATTERN_RENEWAL_ID'),
    ],

    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'mode' => env('ZARINPAL_MODE', 'normal'),
    ],

];