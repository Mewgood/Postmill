<?php

namespace App\Tests\Serializer;

use App\Markdown\MarkdownConverter;
use App\Serializer\Contracts\NormalizeMarkdownInterface;
use App\Serializer\MarkdownNormalizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MarkdownNormalizerTest extends TestCase {
    public function testSupportMethodReturnsTrueForCorrectDataTypes(): void {
        /** @var MarkdownConverter|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(MarkdownConverter::class);
        $normalizer = new MarkdownNormalizer($converter);
        $data = $this->createMock(NormalizeMarkdownInterface::class);

        $this->assertTrue($normalizer->supportsNormalization($data));
    }

    public function testSupportMethodReturnsFalseToAvoidRecursion(): void {
        /** @var MarkdownConverter|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(MarkdownConverter::class);
        $normalizer = new MarkdownNormalizer($converter);
        $data = $this->createMock(NormalizeMarkdownInterface::class);

        $this->assertFalse($normalizer->supportsNormalization($data, null, [
            MarkdownNormalizer::NORMALIZED_MARKER => true,
        ]));
    }

    public function testCanNormalizeMarkdownFields(): void {
        $entity = new class() implements NormalizeMarkdownInterface {
            public function getMarkdownFields(): iterable {
                return [
                    'header',
                    'body' => 'foo',
                    'footer',
                    'nonexistent',
                ];
            }

            public function getMarkdownContext(): array {
                return ['some' => 'context'];
            }
        };

        /** @var MarkdownConverter|\PHPUnit\Framework\MockObject\MockObject $converter */
        $converter = $this->createMock(MarkdownConverter::class);
        $converter
            ->expects($this->exactly(2))
            ->method('convertToHtmlCached')
            ->withConsecutive(
                $this->equalTo('The header'),
                $this->equalTo('The body')
            )
            ->willReturnOnConsecutiveCalls(
                'rendered header',
                'rendered body'
            );

        /** @var NormalizerInterface|\PHPUnit\Framework\MockObject\MockObject $decoratedNormalizer */
        $decoratedNormalizer = $this->createMock(NormalizerInterface::class);
        $decoratedNormalizer
            ->expects($this->once())
            ->method('normalize')
            ->with(
                $this->equalTo($entity),
                $this->isNull(),
                $this->equalTo([MarkdownNormalizer::NORMALIZED_MARKER => true])
            )
            ->willReturn([
                'header' => 'The header',
                'body' => 'The body',
                'footer' => null,
            ]);

        $normalizer = new MarkdownNormalizer($converter);
        $normalizer->setNormalizer($decoratedNormalizer);

        $this->assertEquals([
            'header' => 'The header',
            'renderedHeader' => 'rendered header',
            'body' => 'The body',
            'foo' => 'rendered body',
            'footer' => null,
            'renderedFooter' => null,
        ], $normalizer->normalize($entity));
    }
}
