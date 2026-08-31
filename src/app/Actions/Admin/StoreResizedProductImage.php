<?php

namespace App\Actions\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreResizedProductImage
{
    public const MAX_WIDTH = 500;

    public function handle(
        UploadedFile $upload,
        string $relativeDirectory,
        string $filenamePrefix,
        string $validationKey,
    ): string {
        $directory = public_path($relativeDirectory);
        File::ensureDirectoryExists($directory);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw ValidationException::withMessages([
                $validationKey => 'Unable to save product pictures. Please contact support.',
            ]);
        }

        $mimeType = (string) $upload->getMimeType();
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages([
                $validationKey => 'Only JPG, PNG, and WebP pictures are supported.',
            ]),
        };

        $source = $this->imageResourceFromUpload($upload, $mimeType);

        if (! $source instanceof \GdImage) {
            throw ValidationException::withMessages([
                $validationKey => 'Unable to process the selected image.',
            ]);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $targetWidth = min(self::MAX_WIDTH, $sourceWidth);
        $targetHeight = max(1, (int) round($sourceHeight * ($targetWidth / $sourceWidth)));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $target instanceof \GdImage) {
            throw ValidationException::withMessages([
                $validationKey => 'Unable to process the selected image.',
            ]);
        }

        if (in_array($mimeType, ['image/png', 'image/webp'], true)) {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefill($target, 0, 0, $transparent);
        } else {
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefill($target, 0, 0, $white);
        }

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        );

        $filename = $filenamePrefix.Str::uuid().'.'.$extension;
        $relativePath = trim($relativeDirectory, '/').'/'.$filename;
        $absolutePath = public_path($relativePath);
        $saved = $this->saveImage($target, $mimeType, $absolutePath);

        unset($source, $target);

        if (! $saved) {
            File::delete($absolutePath);

            throw ValidationException::withMessages([
                $validationKey => 'Unable to save product pictures. Please contact support.',
            ]);
        }

        return $relativePath;
    }

    private function imageResourceFromUpload(UploadedFile $upload, string $mimeType): \GdImage|false
    {
        return match ($mimeType) {
            'image/png' => @imagecreatefrompng($upload->getRealPath()),
            'image/webp' => @imagecreatefromwebp($upload->getRealPath()),
            default => @imagecreatefromjpeg($upload->getRealPath()),
        };
    }

    private function saveImage(\GdImage $image, string $mimeType, string $absolutePath): bool
    {
        return match ($mimeType) {
            'image/png' => imagepng($image, $absolutePath, 6),
            'image/webp' => imagewebp($image, $absolutePath, 85),
            default => imagejpeg($image, $absolutePath, 85),
        };
    }
}
