<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ImageService
{
    public function upload(
        UploadedFile $file,
        string $folder = 'ads'
    ): string {
        $filename = uniqid('', true) . '.' . $file->getClientOriginalExtension();

        $path = $folder . '/' . $filename;

        $manager = ImageManager::gd();

        $image = $manager->read($file->getRealPath());

        $image->scaleDown(1200, 900);

        $encoded = $image->encodeByExtension(
            $file->getClientOriginalExtension(),
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