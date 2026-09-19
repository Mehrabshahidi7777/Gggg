<?php

if (! function_exists('format_price')) {
    function format_price($price): string
    {
        return $price === null || $price === '' ? 'توافقی' : number_format((float) $price).' تومان';
    }
}

if (! function_exists('fa_to_en_digits')) {
    /*
    |--------------------------------------------------------------------------
    | تبدیل ارقام فارسی/عربی به انگلیسی
    |--------------------------------------------------------------------------
    |
    | کیبورد فارسی ارقام ۰-۹ را به‌صورت کاراکترهای یونیکدِ فارسی
    | (U+06F0..U+06F9) یا عربی (U+0660..U+0669) می‌فرستد. اینها از نظر
    | PHP «رقم» نیستند: is_numeric کاذب می‌شود، preg با \d نمی‌گیردشان،
    | و لینک tel: با آنها کار نمی‌کند.
    |
    | نتیجه‌اش در دیتابیس فعلی دیده می‌شود: در جدول orders شماره‌هایی
    | مثل «۰۹۱۳۴۴۵۱۵۰۲» و «۹۹۹» ثبت شده که هیچ‌کدام قابل شماره‌گیری
    | نیستند. این تابع ورودی را قبل از اعتبارسنجی یکدست می‌کند.
    |
    */
    function fa_to_en_digits(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace(
            array_merge($persian, $arabic),
            array_merge($english, $english),
            $value
        );
    }
}

if (! function_exists('normalize_mobile')) {
    /*
    |--------------------------------------------------------------------------
    | یکدست‌سازی شماره موبایل ایران
    |--------------------------------------------------------------------------
    |
    | همه‌ی شکل‌های رایج را به قالب واحد 09xxxxxxxxx تبدیل می‌کند:
    |
    |   ۰۹۱۲۱۲۳۴۵۶۷ → 09121234567   (ارقام فارسی)
    |   +989121234567 → 09121234567
    |   00989121234567 → 09121234567
    |   989121234567 → 09121234567
    |   9121234567 → 09121234567
    |   0912-123-4567 → 09121234567
    |
    | اگر ورودی به هیچ‌کدام از این قالب‌ها نخورد، همان رقم‌های
    | استخراج‌شده برگردانده می‌شود تا قانون اعتبارسنجی خودش ردش کند.
    |
    */
    function normalize_mobile(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', (string) fa_to_en_digits($value)) ?? '';

        if ($digits === '') {
            return '';
        }

        // 0098... یا 98...
        if (str_starts_with($digits, '0098')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }

        // 9xxxxxxxxx → 09xxxxxxxxx
        if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
            $digits = '0' . $digits;
        }

        return $digits;
    }
}
