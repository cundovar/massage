<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaUploader
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_WIDTH = 2200;
    private const MAX_HEIGHT = 2200;
    private const JPEG_QUALITY = 82;
    private const WEBP_QUALITY = 82;
    private const PNG_COMPRESSION = 8;

    public function __construct(private readonly string $imagesDirectory)
    {
    }

    /** @return array{filename: string, mimeType: string, sizeBytes: int, width: ?int, height: ?int} */
    public function upload(UploadedFile $file): array
    {
        if (!is_dir($this->imagesDirectory) || !is_writable($this->imagesDirectory)) {
            throw new \InvalidArgumentException('Images directory is not writable.');
        }

        $mimeType = (string) $file->getMimeType();
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException('Unsupported image mime type.');
        }

        $extension = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new \InvalidArgumentException('Unsupported image mime type.'),
        };

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetPath = $this->imagesDirectory . DIRECTORY_SEPARATOR . $filename;

        try {
            $file->move($this->imagesDirectory, $filename);
            $this->resizeIfNeeded($targetPath, $mimeType);
            $dimensions = @getimagesize($targetPath);
            $sizeBytes = (int) filesize($targetPath);
        } catch (\Throwable $exception) {
            throw new \InvalidArgumentException('Unable to save uploaded image.');
        }

        return [
            'filename' => $filename,
            'mimeType' => $mimeType,
            'sizeBytes' => $sizeBytes,
            'width' => is_array($dimensions) ? (int) ($dimensions[0] ?? 0) : null,
            'height' => is_array($dimensions) ? (int) ($dimensions[1] ?? 0) : null,
        ];
    }

    public function remove(string $filename): void
    {
        $filePath = $this->imagesDirectory . DIRECTORY_SEPARATOR . $filename;

        if (is_file($filePath)) {
            @unlink($filePath);
        }
    }

    private function resizeIfNeeded(string $targetPath, string $mimeType): void
    {
        // No GD available: keep original file untouched.
        if (!function_exists('imagecreatetruecolor') || !function_exists('imagecopyresampled')) {
            return;
        }

        $dimensions = @getimagesize($targetPath);
        if (!is_array($dimensions)) {
            return;
        }

        $sourceWidth = (int) ($dimensions[0] ?? 0);
        $sourceHeight = (int) ($dimensions[1] ?? 0);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            return;
        }

        $ratio = min(
            self::MAX_WIDTH / $sourceWidth,
            self::MAX_HEIGHT / $sourceHeight,
            1
        );

        // Already within limits: keep original quality/encoding.
        if ($ratio >= 1) {
            return;
        }

        $targetWidth = max(1, (int) round($sourceWidth * $ratio));
        $targetHeight = max(1, (int) round($sourceHeight * $ratio));

        $sourceImage = $this->createImageResource($targetPath, $mimeType);
        if ($sourceImage === null) {
            return;
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($targetImage === false) {
            imagedestroy($sourceImage);
            return;
        }

        if ($mimeType === 'image/png' || $mimeType === 'image/webp') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $tmpPath = $targetPath . '.tmp';
        $saved = $this->saveImageResource($targetImage, $tmpPath, $mimeType);

        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if (!$saved) {
            @unlink($tmpPath);
            return;
        }

        @rename($tmpPath, $targetPath);
    }

    private function createImageResource(string $path, string $mimeType)
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagecreatefromjpeg') ? @imagecreatefromjpeg($path) : null,
            'image/png' => function_exists('imagecreatefrompng') ? @imagecreatefrompng($path) : null,
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    private function saveImageResource($image, string $path, string $mimeType): bool
    {
        return match ($mimeType) {
            'image/jpeg' => function_exists('imagejpeg')
                ? imagejpeg($image, $path, self::JPEG_QUALITY)
                : false,
            'image/png' => function_exists('imagepng')
                ? imagepng($image, $path, self::PNG_COMPRESSION)
                : false,
            'image/webp' => function_exists('imagewebp')
                ? imagewebp($image, $path, self::WEBP_QUALITY)
                : false,
            default => false,
        };
    }
}
