<?php

namespace App\Message\Handler;

use App\Entity\Submission;
use App\Message\NewSubmission;
use App\Repository\ImageRepository;
use App\Repository\SiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Embed\Embed;
use Embed\Exceptions\EmbedException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DownloadSubmissionImageHandler implements MessageHandlerInterface {
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var HttpClientInterface
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
     * @var SiteRepository
     */
    private $sites;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        HttpClientInterface $submissionImageClient,
        ImageRepository $images,
        LoggerInterface $logger,
        SiteRepository $sites,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->httpClient = $submissionImageClient;
        $this->images = $images;
        $this->logger = $logger;
        $this->sites = $sites;
        $this->validator = $validator;
    }

    public function __invoke(NewSubmission $message): void {
        if (!$this->sites->findCurrentSite()->isUrlImagesEnabled()) {
            $this->logger->info('Image downloading disabled in site settings');

            return;
        }

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
            $response = $this->httpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'image/jpeg, image/gif, image/png',
                ],
            ]);

            $fh = fopen($tempFile, 'wb');
            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($fh, $chunk->getContent());
            }
            fclose($fh);

            return $tempFile;
        } catch (HttpExceptionInterface $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
            ]);

            return null;
        }
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
