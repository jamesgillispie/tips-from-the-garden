<?php

namespace App\Pipeline\Contracts;

use App\Pipeline\Data\ArticleDraft;
use App\Pipeline\Data\WriteRequest;

interface WriterContract
{
    /**
     * Turn a transcript into an article draft, guided by an article
     * template (structure) and the user's voice profile (style).
     */
    public function write(WriteRequest $request): ArticleDraft;

    /**
     * Distill a set of writing samples into a reusable voice profile —
     * concrete style notes injected into future write() prompts.
     *
     * @param  array<int, string>  $samples
     */
    public function summarizeStyle(array $samples): string;

    /**
     * Identifier stored on the article, e.g. "anthropic:claude-opus-4-6".
     */
    public function identifier(): string;
}
