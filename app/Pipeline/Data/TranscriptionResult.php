<?php

namespace App\Pipeline\Data;

class TranscriptionResult
{
    public function __construct(
        public readonly string $text,
        public readonly ?float $durationSeconds = null,
    ) {}
}
