<?php

namespace App\Serializer\Contracts;

interface NormalizeMarkdownInterface {
    /**
     * Map of Markdown fields and their resulting names in the serialized
     * output.
     */
    public function getMarkdownFields(): iterable;

    /**
     * Data to pass to `MarkdownConverter::convert()`'s second parameter.
     */
    public function getMarkdownContext(): array;
}
