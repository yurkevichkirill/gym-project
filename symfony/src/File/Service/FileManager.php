<?php

declare(strict_types=1);

namespace App\File\Service;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class FileManager
{
    /**
     * @throws FilesystemException
     */
    public function upload(
        FilesystemOperator $storage,
        UploadedFile $file,
        string $directory,
        string $prefix = 'file'
    ): string {
        $extension = $file->guessExtension() ?? 'jpg';
        $uniqueName = uniqid($prefix . '_', true) . '.' . $extension;

        $path = $directory . '/' . $uniqueName;

        $storage->writeStream(
            $path,
            fopen($file->getPathname(), 'r')
        );

        return $path;
    }

    /**
     * @throws FilesystemException
     */
    public function delete(FilesystemOperator $storage, ?string $path): void
    {
        if ($path !== null && $storage->has($path)) {
            $storage->delete($path);
        }
    }
}
