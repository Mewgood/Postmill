<?php

namespace App\Message\Handler;

use App\Entity\Submission;
use App\Message\NewSubmission;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Embed\Embed;
use Embed\Exceptions\EmbedException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class DownloadSubmissionImageHandler implements MessageHandlerInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var ImageRepository
     */
    private $images;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        ClientInterface $submissionImageClient,
        EntityManagerInterface $entityManager,
        ImageRepository $images,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->httpClient = $submissionImageClient;
        $this->images = $images;
        $this->logger = $logger;
        $this->validator = $validator;
    }

    public function __invoke(NewSubmission $message): void {
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

        if (!$imageUrl) {
            return;
        }

        $tempFile = $this->downloadImage($imageUrl);

        if (!$tempFile) {
            return;
        }

        try {
            if (!$this->validateImage($tempFile)) {
                return;
            }

            $image = $this->images->findOrCreateFromPath($tempFile);

            $this->entityManager->transactional(static function () use ($submission, $image): void {
                $submission->setImage($image);
            });
        } catch (\RuntimeException $e) {
            throw new UnrecoverableMessageHandlingException($e->getMessage(), $e->getCode(), $e);
        } finally {
            @unlink($tempFile);
        }
    }

    private function getRemoteImageUrl($url): ?string {
        try {
            return Embed::create($url)->getImage();
        } catch (EmbedException $e) {
            $this->logger->notice($e->getMessage(), ['exception' => $e]);
        }

        return null;
    }

    private function downloadImage(string $url): ?string {
        $tempFile = @tempnam(sys_get_temp_dir(), 'pml');

        if ($tempFile === false) {
            throw new UnrecoverableMessageHandlingException('Couldn\'t create temporary file');
        }

        try {
            $this->httpClient->get($url, ['sink' => $tempFile]);
        } catch (TransferException $e) {
            $tempFile = null;
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

            return false;
        }

        return true;
    }
}
