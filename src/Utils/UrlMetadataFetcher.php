<?php

namespace App\Utils;

use App\Utils\Exception\ImageDownloadTooLargeException;
use Embed\Embed;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UrlMetadataFetcher implements UrlMetadataFetcherInterface {
    public const DEFAULT_MAX_IMAGE_BYTES = 4000000;

    private Embed $embed;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private ValidatorInterface $validator;
    private int $maxImageBytes;

    public function __construct(
        Embed $embed,
        HttpClientInterface $externalClient,
        LoggerInterface $logger,
        ValidatorInterface $validator,
        int $maxImageBytes = self::DEFAULT_MAX_IMAGE_BYTES
    ) {
        $this->embed = $embed;
        $this->httpClient = $externalClient;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->maxImageBytes = $maxImageBytes;
    }

    public function fetchTitle(string $url): ?string {
        return $this->embed->get($url)->title;
    }

    public function downloadRepresentativeImage(string $url): ?string {
        $url = $this->embed->get($url)->image;

        if ($url === null) {
            return null;
        }

        $tempFile = @tempnam(sys_get_temp_dir(), 'postmill-downloads');

        if (!\is_string($tempFile)) {
            throw new \RuntimeException('Failed to create temporary directory');
        }

        $fh = fopen($tempFile, 'wb');

        try {
            $response = $this->httpClient->request('GET', (string) $url, [
                'headers' => [
                    'Accept' => 'image/jpeg, image/gif, image/png',
                ],
                'on_progress' => function (int $downloaded, int $downloadSize) {
                    if (
                        $downloaded > $this->maxImageBytes ||
                        $downloadSize > $this->maxImageBytes
                    ) {
                        throw new ImageDownloadTooLargeException(
                            $this->maxImageBytes,
                            max($downloadSize, $downloaded),
                        );
                    }
                },
            ]);

            foreach ($this->httpClient->stream($response) as $chunk) {
                fwrite($fh, $chunk->getContent());
            }

            fclose($fh);
        } catch (\Throwable $e) {
            fclose($fh);
            unlink($tempFile);

            if ($e->getPrevious() instanceof ImageDownloadTooLargeException) {
                $this->logger->debug($e->getMessage());

                return null;
            }

            throw $e;
        }

        if (!$this->validateImage($tempFile)) {
            unlink($tempFile);

            return null;
        }

        return $tempFile;
    }

    private function validateImage(string $path): bool {
        $errors = $this->validator->validate($path, new Image([
            'detectCorrupted' => true,
        ]));

        return \count($errors) === 0;
    }
}
