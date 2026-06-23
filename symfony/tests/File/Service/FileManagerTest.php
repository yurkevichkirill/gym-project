<?php

declare(strict_types=1);

namespace App\Tests\File\Service;

use App\File\Service\FileManager;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class FileManagerTest extends TestCase
{
    private string $temporaryFile;

    protected function setUp(): void
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'file-manager-test-');
        if ($temporaryFile === false) {
            throw new RuntimeException('Unable to create temporary test file.');
        }

        file_put_contents($temporaryFile, 'uploaded file content');
        $this->temporaryFile = $temporaryFile;
    }

    protected function tearDown(): void
    {
        if (isset($this->temporaryFile) && is_file($this->temporaryFile)) {
            unlink($this->temporaryFile);
        }

        parent::tearDown();
    }

    public function testUploadUsesSecureRandomFileName(): void
    {
        $uploadedFile = new UploadedFile(
            path: $this->temporaryFile,
            originalName: 'avatar.txt',
            mimeType: 'text/plain',
            error: UPLOAD_ERR_OK,
            test: true,
        );

        $writtenPath = null;
        $storage = $this->createMock(FilesystemOperator::class);
        $storage
            ->expects(self::once())
            ->method('writeStream')
            ->willReturnCallback(
                static function (string $path, mixed $stream) use (&$writtenPath): void {
                    self::assertIsResource($stream);
                    $writtenPath = $path;
                },
            );

        $path = (new FileManager())->upload(
            storage: $storage,
            file: $uploadedFile,
            directory: 'trainers',
            prefix: 'avatar',
        );

        self::assertSame($writtenPath, $path);
        self::assertMatchesRegularExpression(
            '#^trainers/avatar_[a-f0-9]{32}\.(?:txt|jpg)$#',
            $path,
        );
    }
}
