<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MediaUploader
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

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
}
