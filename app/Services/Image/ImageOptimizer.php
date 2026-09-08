<?php

namespace App\Services\Image;

use Filament\Forms\Components\BaseFileUpload;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ImageOptimizer
{
    /**
     * Convert and optimize an uploaded file to WebP, then store it.
     */
    public static function optimizeAndStore(
        BaseFileUpload $component,
        TemporaryUploadedFile $file,
        int $maxDimension = 1920,
        int $quality = 82
    ): ?string {
        try {
            if (! $file->exists()) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $rawContents = null;
        try {
            $rawContents = $file->get();
        } catch (\Throwable) {
            try {
                $rawContents = file_get_contents($file->getRealPath());
            } catch (\Throwable) {
                $rawContents = null;
            }
        }

        $disk = $component->getDisk();
        $directory = trim((string) $component->getDirectory(), '/');

        // If contents couldn't be read or GD is not available, fallback to standard Filament storage
        if (empty($rawContents) || ! extension_loaded('gd') || ! function_exists('imagewebp')) {
            return $component->saveUploadedFile($file);
        }

        // SVGs or GIFs with multiple frames: preserve as-is
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if ($ext === 'svg' || $ext === 'gif') {
            return $component->saveUploadedFile($file);
        }

        $image = @imagecreatefromstring($rawContents);
        if (! $image) {
            return $component->saveUploadedFile($file);
        }

        // Support alpha channel transparency for PNG / WebP
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        $width = imagesx($image);
        $height = imagesy($image);

        // Downscale large photos to max dimension while preserving aspect ratio
        if ($width > $maxDimension || $height > $maxDimension) {
            if ($width > $height) {
                $newWidth = $maxDimension;
                $newHeight = (int) round($height * ($maxDimension / $width));
            } else {
                $newHeight = $maxDimension;
                $newWidth = (int) round($width * ($maxDimension / $height));
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        // Encode into WebP binary buffer
        ob_start();
        imagewebp($image, null, $quality);
        $webpContents = ob_get_clean();
        imagedestroy($image);

        if (empty($webpContents)) {
            return $component->saveUploadedFile($file);
        }

        // Generate filename with .webp extension
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slugName = Str::slug($baseName);
        if ($component->shouldPreserveFilenames() && filled($slugName)) {
            $fileName = $slugName . '-' . Str::random(6) . '.webp';
        } else {
            $fileName = Str::ulid() . '.webp';
        }

        $path = $directory !== '' ? "{$directory}/{$fileName}" : $fileName;

        // Write directly to component disk (works seamlessly for vercel_blob and local/static_images)
        $disk->put($path, $webpContents, [
            'visibility' => 'public',
            'ContentType' => 'image/webp',
        ]);

        return $path;
    }
}
