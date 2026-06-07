<?php

namespace App\Pipeline\Data;

class ArticleDraft
{
    public function __construct(
        public readonly string $title,
        public readonly string $bodyMarkdown,
    ) {}
}
