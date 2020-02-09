<?php

namespace App\Tests\Serializer;

use App\DataObject\SubmissionData;
use App\Entity\Image;
use App\Serializer\SubmissionDataNormalizer;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @covers \App\Serializer\SubmissionDataNormalizer
 */
class SubmissionDataNormalizerTest extends TestCase {
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CacheManager
     */
    private $cacheManager;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|NormalizerInterface
     */
    private $decorated;

    /**
     * @var SubmissionDataNormalizer
     */
    private $normalizer;

    protected function setUp(): void {
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->decorated = $this->createMock(NormalizerInterface::class);
        $this->normalizer = new SubmissionDataNormalizer($this->cacheManager);
        $this->normalizer->setNormalizer($this->decorated);
    }

    public function testSupportsSubmissionData(): void {
        $data = new SubmissionData();

        $this->assertTrue($this->normalizer->supportsNormalization($data));
    }

    public function testAddsImagePathsToNormalizedData(): void {
        $this->cacheManager
            ->expects($this->exactly(2))
            ->method('generateUrl')
            ->withConsecutive(
                ['foo.png', 'submission_thumbnail_1x'],
                ['foo.png', 'submission_thumbnail_2x']
            )
            ->willReturnOnConsecutiveCalls(
                'http://localhost/1x/foo.png',
                'http://localhost/2x/foo.png'
            );

        $data = new SubmissionData();
        $data->setImage(new Image('foo.png', null, null));

        $this->decorated
            ->expects($this->once())
            ->method('normalize')
            ->with($data)
            ->willReturn(['image' => 'foo.png']);

        $normalized = $this->normalizer->normalize($data);

        $this->assertSame('http://localhost/1x/foo.png', $normalized['thumbnail_1x']);
        $this->assertSame('http://localhost/2x/foo.png', $normalized['thumbnail_2x']);
    }
}
