<?php

namespace App\Actions\Admin;

use App\Models\AgentEmailTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class SyncAgentEmailTemplateImages
{
    /**
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, string>  $removeImagePaths
     */
    public function handle(AgentEmailTemplate $template, array $uploads, array $removeImagePaths): void
    {
        $currentPaths = collect($template->imagePaths());
        $removedPaths = collect($removeImagePaths)
            ->filter(static fn ($path): bool => is_string($path))
            ->intersect($currentPaths)
            ->values();
        $imagePaths = $currentPaths
            ->reject(static fn (string $path): bool => $removedPaths->contains($path))
            ->values();
        $storedPaths = [];

        try {
            foreach ($uploads as $upload) {
                $path = $this->store($upload, $template);
                $storedPaths[] = $path;
                $imagePaths->push($path);
            }

            if ($imagePaths->count() > AgentEmailTemplate::MAX_IMAGES) {
                throw ValidationException::withMessages([
                    'template_images' => 'An email template can have a maximum of 4 images.',
                ]);
            }

            $template->forceFill(['image_paths' => $imagePaths->all()])->save();
        } catch (Throwable $exception) {
            $this->deleteManagedFiles($storedPaths);

            throw $exception;
        }

        $this->deleteManagedFiles($removedPaths);
    }

    private function store(UploadedFile $upload, AgentEmailTemplate $template): string
    {
        $relativeDirectory = "images/agent-email-templates/{$template->getKey()}";
        $directory = public_path($relativeDirectory);
        File::ensureDirectoryExists($directory);

        if (! is_dir($directory) || ! is_writable($directory)) {
            throw ValidationException::withMessages([
                'template_images' => 'Unable to save the email images. Please contact support.',
            ]);
        }

        $mimeType = (string) $upload->getMimeType();
        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages([
                'template_images' => 'Only JPG, PNG, and WebP images are supported.',
            ]),
        };

        $source = $this->imageResourceFromUpload($upload, $mimeType);

        if (! $source instanceof \GdImage) {
            throw ValidationException::withMessages([
                'template_images' => 'Unable to process one of the selected images.',
            ]);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $scale = min(1, AgentEmailTemplate::MAX_IMAGE_DIMENSION / max($sourceWidth, $sourceHeight));
        $targetWidth = max(1, (int) round($sourceWidth * $scale));
        $targetHeight = max(1, (int) round($sourceHeight * $scale));
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        if (! $target instanceof \GdImage) {
            throw ValidationException::withMessages([
                'template_images' => 'Unable to process one of the selected images.',
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

        $filename = 'image-'.Str::uuid().".{$extension}";
        $relativePath = "{$relativeDirectory}/{$filename}";
        $absolutePath = public_path($relativePath);
        $saved = $this->saveImage($target, $mimeType, $absolutePath);

        unset($source, $target);

        if (! $saved) {
            File::delete($absolutePath);

            throw ValidationException::withMessages([
                'template_images' => 'Unable to save the email images. Please contact support.',
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

    /**
     * @param  iterable<int, string>  $paths
     */
    private function deleteManagedFiles(iterable $paths): void
    {
        foreach ($paths as $path) {
            if (Str::startsWith($path, 'images/agent-email-templates/')) {
                File::delete(public_path($path));
            }
        }
    }
}
