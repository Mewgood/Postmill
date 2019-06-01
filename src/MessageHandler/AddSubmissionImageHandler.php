<?php

namespace App\MessageHandler;

use App\Entity\Submission;
use App\Message\NewSubmission;
use Doctrine\ORM\EntityManagerInterface;
use Embed\Embed;
use Embed\Exceptions\EmbedException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AddSubmissionImageHandler implements MessageHandlerInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        Client $httpClient,
        EntityManagerInterface $entityManager,
        FilesystemInterface $filesystem,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->filesystem = $filesystem;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    public function __invoke(NewSubmission $message) {
        $id = $message->getSubmissionId();
        $submission = $this->entityManager->find(Submission::class, $id);

        if (!$submission instanceof Submission) {
            throw new UnrecoverableMessageHandlingException(
                "Submission with ID {$id} not found"
            );
        }

        if (!$submission->getUrl() || $submission->getImage()) {
            return;
        }

        $imageUrl = $this->getRemoteImageUrl($submission->getUrl());

        if ($imageUrl) {
            $tempFile = $this->downloadImage($imageUrl);

            if ($tempFile && $this->validateImage($tempFile)) {
                $imageName = $this->getFileName($tempFile);
                $stored = $this->storeImage($tempFile, $imageName);

                if ($stored) {
                    $this->entityManager->transactional(
                        function () use ($submission, $imageName) {
                            $submission->setImage($imageName);
                        }
                    );
                }
            }
        }
    }

    private function getRemoteImageUrl($url): ?string {
        try {
            $embed = Embed::create($url);

            return $embed->getImage();
        } catch (EmbedException $e) {
            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }
    }

    private function downloadImage(string $url): ?string {
        $tempFile = @\tempnam(\sys_get_temp_dir(), 'pml');

        if ($tempFile === false) {
            throw new UnrecoverableMessageHandlingException('Couldn\'t create temporary file');
        }

        try {
            $this->httpClient->get($url, ['sink' => $tempFile]);
        } catch (TransferException $e) {
            @unlink($tempFile);
            $tempFile = null;
        } catch (GuzzleException $e) {
            @unlink($tempFile);

            throw $e;
        }

        return $tempFile;
    }

    private function validateImage(string $fileName): bool {
        $violations = $this->validator->validate($fileName, [
            new Image(['detectCorrupted' => true]),
        ]);

        if (\count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->logger->debug(
                    $violation->getMessageTemplate(),
                    $violation->getParameters()
                );
            }

            @\unlink($fileName);

            return false;
        }

        return true;
    }

    private function getFileName($fileName): string {
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($fileName);

        if (!$mimeType) {
            @\unlink($fileName);

            throw new UnrecoverableMessageHandlingException(
                'Couldn\'t guess MIME type of image'
            );
        }

        $ext = $mimeTypes->getExtensions($mimeType)[0] ?? null;

        if (!$ext) {
            @\unlink($fileName);

            throw new UnrecoverableMessageHandlingException(
                'Couldn\'t guess extension of image'
            );
        }

        return \sprintf('%s.%s', \hash_file('sha256', $fileName), $ext);
    }

    private function storeImage(string $source, string $destination): bool {
        $fh = \fopen($source, 'rb');

        if (!$fh) {
            @\unlink($source);

            throw new UnrecoverableMessageHandlingException("Couldn't open image for reading");
        }

        try {
            $success = $this->filesystem->writeStream($destination, $fh);
        } catch (FileExistsException $e) {
            $success = true;
        } finally {
            @\unlink($source);
        }

        return $success;
    }
}
