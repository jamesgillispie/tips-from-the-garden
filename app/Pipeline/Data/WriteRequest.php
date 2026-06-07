<?php

namespace App\Pipeline\Data;

use App\Models\ArticleTemplate;

class WriteRequest
{
    public function __construct(
        public readonly string $transcript,
        public readonly ?ArticleTemplate $template = null,
        public readonly ?string $voiceProfile = null,
        public readonly ?string $authorName = null,
    ) {}
}
