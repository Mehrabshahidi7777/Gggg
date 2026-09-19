<?php

namespace Tests\Feature;

use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| آپلود تصویر
|--------------------------------------------------------------------------
|
| پوشه‌ی مقصد داخل DocumentRoot است، پس نام فایلِ ذخیره‌شده اهمیت
| امنیتی دارد. این تست‌ها تضمین می‌کنند پسوند از روی محتوای واقعی
| فایل انتخاب شود، نه از روی نامی که کاربر فرستاده.
|
*/
class ImageUploadTest extends TestCase
{
    public function test_extension_comes_from_real_content_not_the_sent_filename(): void
    {
        Storage::fake('public');

        /*
        | یک PNG واقعی که کاربر با نام «عکس.php.png» فرستاده. پسوند
        | ذخیره‌شده باید png باشد و هیچ اثری از php در مسیر نماند.
        */
        $file = UploadedFile::fake()->image('عکس.php.png', 50, 50);

        $path = app(ImageService::class)->upload($file);

        $this->assertStringEndsWith('.png', $path);
        $this->assertStringNotContainsString('php', $path);
        $this->assertStringStartsWith('ads/', $path);

        Storage::disk('public')->assertExists($path);
    }

    public function test_jpeg_is_normalised_to_jpg(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('photo.jpeg', 40, 40);

        $path = app(ImageService::class)->upload($file);

        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_non_image_mime_is_rejected(): void
    {
        Storage::fake('public');

        $this->expectException(\RuntimeException::class);

        app(ImageService::class)->upload(
            UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php')
        );
    }
}
