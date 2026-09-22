<?php
return ['default'=>env('LOG_CHANNEL','stack'),'deprecations'=>['trace'=>false,'channel'=>env('LOG_DEPRECATIONS_CHANNEL','null')],'channels'=>['stack'=>['driver'=>'stack','channels'=>explode(',',env('LOG_STACK','single')),'ignore_exceptions'=>false],'single'=>['driver'=>'single','path'=>storage_path('logs/laravel.log'),'level'=>env('LOG_LEVEL','debug'),'replace_placeholders'=>true],

    /*
    |--------------------------------------------------------------------------
    | کانال کارهای زمان‌بند
    |--------------------------------------------------------------------------
    |
    | ⚠️ level اینجا عمداً ثابت است و از LOG_LEVEL پیروی نمی‌کند.
    |
    | اگر .env روی error باشد - که در تولید معمول است - پیام‌های info
    | و warning بی‌صدا دور ریخته می‌شوند. برای کارهای شبانه این یعنی
    | همان سکوتی که ماه‌ها پنهانشان کرد: خروجی کرون به /dev/null
    | می‌رود و لاگ هم چیزی نمی‌نویسد.
    |
    | فایل هم جداست تا کاربر - که فقط می‌تواند فایل دانلود کند و
    | پرس‌وجوی SQL ندارد - یک فایل کوچکِ چندخطی بفرستد، نه کل
    | laravel.log.
    |
    */
    'cron'=>['driver'=>'single','path'=>storage_path('logs/cron-tasks.log'),'level'=>'debug','replace_placeholders'=>true],
'daily'=>['driver'=>'daily','path'=>storage_path('logs/laravel.log'),'level'=>env('LOG_LEVEL','debug'),'days'=>14,'replace_placeholders'=>true],'stderr'=>['driver'=>'monolog','level'=>env('LOG_LEVEL','debug'),'handler'=>Monolog\Handler\StreamHandler::class,'formatter'=>env('LOG_STDERR_FORMATTER'),'with'=>['stream'=>'php://stderr'],'processors'=>[Monolog\Processor\PsrLogMessageProcessor::class]],'null'=>['driver'=>'monolog','handler'=>Monolog\Handler\NullHandler::class],'emergency'=>['path'=>storage_path('logs/laravel.log')]]];
