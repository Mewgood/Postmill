<?php

namespace App\Flysystem;

use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Mime\MimeTypes;

final class ImageManager {
    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    public function __construct(FilesystemInterface $submissionImages) {
        $this->filesystem = $submissionImages;
    }

    /**
     * Gets filename based on file's hash and extension.
     *
     * @throws \RuntimeException if MIME type couldn't be guessed
     */
    public function getFileName(string $file): string {
        $hash = hash_file('sha256', $file);

        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($file);

        if (!$mimeType) {
            throw new \RuntimeException("Couldn't guess MIME type of image");
        }

        $ext = $mimeTypes->getExtensions($mimeType)[0] ?? null;

        if (!$ext) {
            throw new \RuntimeException("Couldn't guess extension of image");
        }

        return sprintf('%s.%s', $hash, $ext);
    }

    /**
     * Store image using Flysystem instance.
     *
     * @throws \RuntimeException if file couldn't be stored
     */
    public function store(string $source, string $destination): void {
        $fh = fopen($source, 'rb');

        try {
            $success = $this->filesystem->writeStream($destination, $fh);

            if (!$success) {
                throw new \RuntimeException("Couldn't store file");
            }
        } catch (FileExistsException $e) {
            // do nothing
        } finally {
            \is_resource($fh) and fclose($fh);
        }
    }

    public function prune(string $image): void {
        try {
            $this->filesystem->delete($image);
        } catch (FileNotFoundException $e) {
            // do nothing
        }
    }
}
