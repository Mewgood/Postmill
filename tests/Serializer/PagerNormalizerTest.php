<?php

namespace App\Tests\Serializer;

use App\Pagination\Pager;
use App\Serializer\PagerNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @covers \App\Serializer\PagerNormalizer
 */
class PagerNormalizerTest extends TestCase {
    public function testNormalizesPager(): void {
        /** @var NormalizerInterface|\PHPUnit\Framework\MockObject\MockObject $decorated */
        $decorated = $this->createMock(NormalizerInterface::class);
        $decorated
            ->expects($this->once())
            ->method('normalize')
            ->with(['foo', 'bar'], 'json', ['some' => 'context'])
            ->willReturn(['foo', 'bar']);

        $pager = new Pager(['foo', 'bar'], ['mario' => 'luigi']);

        $normalizer = new PagerNormalizer();
        $normalizer->setNormalizer($decorated);

        $this->assertEquals([
            'entries' => ['foo', 'bar'],
            'nextPage' => 'next%5Bmario%5D=luigi',
        ], $normalizer->normalize($pager, 'json', ['some' => 'context']));
    }

    public function testSupportPager(): void {
        $normalizer = new PagerNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new Pager([1, 2, 3], [])));
    }
}
