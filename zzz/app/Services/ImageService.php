<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ImageService
{
    /*
    |--------------------------------------------------------------------------
    | پسوندهای مجاز
    |--------------------------------------------------------------------------
    |
    | پسوند فایل ذخیره‌شده از روی «نوع واقعیِ تصویر» تعیین می‌شود، نه از
    | روی نام فایلی که کاربر فرستاده. نام فایلِ ارسالی کاملاً در کنترل
    | کاربر است و استفاده‌ی مستقیم از آن در مسیر ذخیره‌سازی یعنی
    | سپردن نام‌گذاریِ فایل‌های داخل DocumentRoot به کاربر.
    |
    | قانون‌های AdSubmitRequest (image + mimes) از قبل جلوی فایل غیرتصویر
    | را می‌گیرند؛ این لایه‌ی دوم است تا حتی در صورت تغییر آن قانون‌ها
    | هم چیزی جز این چهار پسوند روی دیسک ننشیند.
    |
    */
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function upload(
        UploadedFile $file,
        string $folder = 'ads'
    ): string {
        $manager = ImageManager::gd();

        $image = $manager->read($file->getRealPath());

        /*
        | getMimeType() از روی محتوای واقعی فایل تشخیص داده می‌شود
        | (finfo)، نه از روی هدر Content-Type که کاربر می‌فرستد.
        */
        $extension = self::ALLOWED[$file->getMimeType()] ?? null;

        if ($extension === null) {
            throw new \RuntimeException('فرمت تصویر پشتیبانی نمی‌شود.');
        }

        $path = $folder . '/' . uniqid('', true) . '.' . $extension;

        $image->scaleDown(1200, 900);

        $encoded = $image->encodeByExtension(
            $extension,
            quality: 82
        );

        Storage::disk('public')->put(
            $path,
            (string) $encoded
        );

        return $path;
    }

    public function delete(?string $path): void
    {
        if (
            $path &&
            Storage::disk('public')->exists($path)
        ) {
            Storage::disk('public')->delete($path);
        }
    }
}