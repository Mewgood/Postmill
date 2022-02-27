<?php

declare(strict_types=1);

namespace App\Tests\Utils;

use App\Utils\UrlMetadataFetcher;
use Closure;
use Embed\Embed;
use Embed\Http\Crawler;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * @covers \App\Utils\UrlMetadataFetcher
 */
final class UrlMetadataFetcherTest extends TestCase {
    private MockHttpClient $client;
    private UrlMetadataFetcher $metadataFetcher;

    protected function setUp(): void {
        $this->client = new MockHttpClient(Closure::fromCallable([$this, 'makeResponse']));
        $embed = new Embed(new Crawler(new Psr18Client($this->client)));
        $validator = (new ValidatorBuilder())->getValidator();

        $this->metadataFetcher = new UrlMetadataFetcher(
            $embed,
            $this->client,
            new NullLogger(),
            $validator
        );
    }

    public function testFetchTitle(): void {
        $this->assertSame(
            'Foo',
            $this->metadataFetcher->fetchTitle('https://example.com/title'),
        );
    }

    public function testFetchTitleWithNoTitleAvailable(): void {
        $this->assertNull(
            $this->metadataFetcher->fetchTitle('https://example.com/blank'),
        );
    }

    public function testDownloadRepresentativeImage(): void {
        $path = $this->metadataFetcher
            ->downloadRepresentativeImage('https://example.com/meta_image');

        $this->assertIsString($path);

        try {
            $this->assertSame(
                'a91d6c2201d32b8c39bff1143a5b29e74b740248c5d65810ddcbfa16228d49e9',
                hash_file('sha256', $path),
            );
        } finally {
            unlink($path);
        }
    }

    public function testDownloadRepresentativeImageWithNoImageAvailable(): void {
        $path = $this->metadataFetcher
            ->downloadRepresentativeImage('https://example.com/title');

        $this->assertNull($path);
    }

    private function makeResponse(string $method, string $uri): MockResponse {
        switch ($uri) {
        case 'https://example.com/blank':
            return new MockResponse('');

        case 'https://example.com/title':
            return new MockResponse(<<<EOHTML
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <title>Foo</title>
                </head>
                <body>
                    <p>Bar</p>
                </body>
            </html>
            EOHTML);

        case 'https://example.com/meta_image':
            return new MockResponse(<<<EOHTML
            <!DOCTYPE html>
            <html lang="en">
                <head>
                    <meta property="og:image" content="https://example.com/image.png">
                    <title>Foo</title>
                </head>
                <body>
                    <p>Bar</p>
                </body>
            </html>
            EOHTML
            );

        case 'https://example.com/image.png':
            return new MockResponse(
                file_get_contents(__DIR__.'/../Resources/120px-12-Color-SVG.svg.png'),
                ['response_headers' => ['Content-Type' => 'image/png']],
            );

        default:
            $this->fail("Unknown URL ($uri)");
        }
    }
}
