<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Resolves upload error codes to human-readable messages.
 */
class UploadErrorResolver
{
    public function resolve(UploadedFile $uploaded): string
    {
        return match ($uploaded->getError()) {
            UPLOAD_ERR_INI_SIZE => sprintf('File exceeds server upload limit (%s).', (string) (ini_get('upload_max_filesize') ?: 'php.ini')),
            UPLOAD_ERR_FORM_SIZE => sprintf('File exceeds form upload limit (%s).', (string) (ini_get('post_max_size') ?: 'form limit')),
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Server failed to write the uploaded file.',
            UPLOAD_ERR_EXTENSION => 'Upload blocked by a server extension.',
            default => 'Invalid upload.',
        };
    }
}
