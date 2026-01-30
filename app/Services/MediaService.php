<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /** @var array<string, int> */
    private const IMAGE_VARIANTS = [
        'full' => 1920,
        'large' => 1024,
        'medium' => 720,
        'small' => 300,
        'thumbnail' => 150,
    ];

    /** @var list<string> */
    private const IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /** @var list<string> */
    private const VIDEO_MIME_TYPES = [
        'video/mp4',
        'video/x-msvideo',
        'video/x-ms-wmv',
        'video/webm',
    ];

    public function upload(UploadedFile $file, ?int $userId = null): Media
    {
        $meta = $this->extractMeta($file);
        $sanitizedName = $this->sanitizeFilename($meta['original_filename']);
        $extension = strtolower($meta['extension']);
        $nameWithoutExt = pathinfo($sanitizedName, PATHINFO_FILENAME);
        $filename = $this->resolveFilenameConflict($nameWithoutExt, $extension);

        $media = Media::query()->create([
            'filename' => $filename,
            'original_filename' => $meta['original_filename'],
            'path' => 'uploads/'.$filename,
            'disk' => 'public',
            'mime_type' => $meta['mime_type'],
            'size' => $meta['size'],
            'type' => $meta['type'],
            'width' => $meta['width'],
            'height' => $meta['height'],
            'duration' => $meta['duration'],
            'uploaded_by' => $userId,
        ]);

        $this->saveFile($file, $filename);

        if ($meta['type'] === MediaType::Image && in_array($meta['mime_type'], self::IMAGE_MIME_TYPES)) {
            $variants = $this->resizeImage($filename, $nameWithoutExt, $extension);
            $media->update(['variants' => $variants]);
        }

        return $media->fresh();
    }

    /**
     * @return array{original_filename: string, extension: string, mime_type: string, size: int, type: MediaType, width: int|null, height: int|null, duration: int|null}
     */
    public function extractMeta(UploadedFile $file): array
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload error: '.$file->getErrorMessage());
        }

        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType() ?? $file->getClientMimeType();
        $size = $file->getSize();
        $type = $this->determineType($mimeType);

        $width = null;
        $height = null;
        $duration = null;

        if ($type === MediaType::Image && in_array($mimeType, self::IMAGE_MIME_TYPES)) {
            $imageSize = @getimagesize($file->getRealPath());
            if ($imageSize !== false) {
                $width = $imageSize[0];
                $height = $imageSize[1];
            }
        }

        return [
            'original_filename' => $originalFilename,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $size,
            'type' => $type,
            'width' => $width,
            'height' => $height,
            'duration' => $duration,
        ];
    }

    public function sanitizeFilename(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $name = pathinfo($filename, PATHINFO_FILENAME);

        $name = strtolower($name);
        $name = Str::ascii($name);
        $name = str_replace(' ', '-', $name);
        $name = preg_replace('/[^a-z0-9\-_]/', '', $name);
        $name = preg_replace('/-{2,}/', '-', $name);
        $name = trim($name, '-');

        return $name.'.'.$extension;
    }

    public function resolveFilenameConflict(string $name, string $extension): string
    {
        $filename = $name.'.'.$extension;
        $counter = 1;

        while (Storage::disk('public')->exists('uploads/'.$filename)) {
            $filename = $name.'_'.$counter.'.'.$extension;
            $counter++;
        }

        return $filename;
    }

    public function saveFile(UploadedFile $file, string $filename): string
    {
        $path = 'uploads/'.$filename;
        Storage::disk('public')->putFileAs('uploads', $file, $filename);

        $fullPath = Storage::disk('public')->path($path);
        if (file_exists($fullPath) && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            chmod($fullPath, 0644);
        }

        return $path;
    }

    /**
     * @return array<string, string>
     */
    public function resizeImage(string $filename, string $name, string $extension): array
    {
        $sourcePath = Storage::disk('public')->path('uploads/'.$filename);
        $sourceImage = $this->createImageFromFile($sourcePath, $extension);

        if (! $sourceImage) {
            return [];
        }

        $sourceImage = $this->fixExifOrientation($sourcePath, $sourceImage, $extension);

        $originalWidth = imagesx($sourceImage);
        $originalHeight = imagesy($sourceImage);

        $variants = [];

        foreach (self::IMAGE_VARIANTS as $variant => $maxWidth) {
            if ($originalWidth <= $maxWidth) {
                $newWidth = $originalWidth;
                $newHeight = $originalHeight;
            } else {
                $newWidth = $maxWidth;
                $newHeight = (int) round(($maxWidth / $originalWidth) * $originalHeight);
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($extension === 'png' || $extension === 'webp') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            $variantFilename = $name.'_'.$variant.'.'.$extension;
            $variantPath = Storage::disk('public')->path('uploads/'.$variantFilename);

            $this->saveImageToFile($resized, $variantPath, $extension);
            imagedestroy($resized);

            $variants[$variant] = 'uploads/'.$variantFilename;
        }

        imagedestroy($sourceImage);

        return $variants;
    }

    public function determineType(string $mimeType): MediaType
    {
        if (in_array($mimeType, self::IMAGE_MIME_TYPES) || $mimeType === 'image/svg+xml') {
            return MediaType::Image;
        }

        if (in_array($mimeType, self::VIDEO_MIME_TYPES)) {
            return MediaType::Video;
        }

        return MediaType::Document;
    }

    private function createImageFromFile(string $path, string $extension): ?\GdImage
    {
        return match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path) ?: null,
            'png' => @imagecreatefrompng($path) ?: null,
            'gif' => @imagecreatefromgif($path) ?: null,
            'webp' => @imagecreatefromwebp($path) ?: null,
            default => null,
        };
    }

    private function saveImageToFile(\GdImage $image, string $path, string $extension): void
    {
        match ($extension) {
            'jpg', 'jpeg' => imagejpeg($image, $path, 85),
            'png' => imagepng($image, $path, 8),
            'gif' => imagegif($image, $path),
            'webp' => imagewebp($image, $path, 85),
            default => null,
        };

        if (file_exists($path) && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            chmod($path, 0644);
        }
    }

    private function fixExifOrientation(string $path, \GdImage $image, string $extension): \GdImage
    {
        if (! in_array($extension, ['jpg', 'jpeg']) || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);

        if (! $exif || ! isset($exif['Orientation'])) {
            return $image;
        }

        $rotated = match ($exif['Orientation']) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        if ($rotated !== $image && $rotated !== false) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    public function deleteMedia(Media $media): void
    {
        if ($media->variants) {
            foreach ($media->variants as $variantPath) {
                Storage::disk($media->disk)->delete($variantPath);
            }
        }

        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}
