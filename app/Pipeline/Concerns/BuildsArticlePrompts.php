<?php

namespace App\Pipeline\Concerns;

use App\Pipeline\Data\ArticleDraft;
use App\Pipeline\Data\WriteRequest;

trait BuildsArticlePrompts
{
    protected function systemPrompt(WriteRequest $request): string
    {
        $parts = [];

        $parts[] = <<<'PROMPT'
You are a ghostwriter for a gardener. You receive the raw transcript of a
voice memo they recorded while walking their garden, and you turn it into a
polished article that sounds like THEM — their words, their knowledge, their
personality — only organized and cleaned up.

Rules:
- Use only the gardener's own observations, facts, and opinions from the
  transcript. Never invent gardening advice they did not give.
- Preserve their distinctive phrases and vocabulary where they work in prose.
- Remove filler (um, uh, you know), false starts, and repetition.
- Keep plant names accurate. If a plant name was likely mis-transcribed,
  prefer the most plausible correct name.
- Write in the first person, as the gardener.
PROMPT;

        if ($request->template !== null) {
            $parts[] = "Structure the article as follows:\n\n"
                .$request->template->structure_prompt
                .($request->template->example_skeleton
                    ? "\n\nExample skeleton:\n".$request->template->example_skeleton
                    : '');
        }

        if ($request->voiceProfile) {
            $parts[] = "Voice and style notes for this writer (follow closely):\n\n"
                .$request->voiceProfile;
        }

        $parts[] = <<<'PROMPT'
Output format — exactly this, nothing else:
- First line: the article title prefixed with "# " (a Markdown H1).
- A blank line.
- The article body in Markdown. Use "## " subheadings where the structure
  calls for them. No preamble, no commentary, no sign-off.
PROMPT;

        return implode("\n\n---\n\n", $parts);
    }

    protected function userPrompt(WriteRequest $request): string
    {
        $author = $request->authorName ? " The gardener's name is {$request->authorName}." : '';

        return "Here is the voice memo transcript.{$author}\n\n<transcript>\n"
            .trim($request->transcript)
            ."\n</transcript>";
    }

    protected function styleSystemPrompt(): string
    {
        return <<<'PROMPT'
You analyze writing samples and produce a compact voice profile: concrete,
actionable style notes a ghostwriter can follow to write new prose that
sounds like this author.

Cover: tone and register, sentence rhythm and length, vocabulary and pet
phrases, how they open and close pieces, how they handle instructions and
asides, punctuation habits, and anything distinctive. Quote short
characteristic phrases as examples.

Output 150-300 words of plain prose notes. No preamble, no headings.
PROMPT;
    }

    /**
     * Parse "# Title\n\nbody..." into an ArticleDraft, tolerating models
     * that forget the heading marker.
     */
    protected function parseDraft(string $raw): ArticleDraft
    {
        $raw = trim($raw);

        // Strip <think>...</think> blocks some local models emit.
        $raw = trim(preg_replace('/^<think>.*?<\/think>/s', '', $raw) ?? $raw);

        $lines = explode("\n", $raw);
        $firstLine = trim($lines[0] ?? '');

        if (str_starts_with($firstLine, '#')) {
            $title = trim(ltrim($firstLine, '# '));
            $body = trim(implode("\n", array_slice($lines, 1)));
        } else {
            $title = mb_substr($firstLine, 0, 120) ?: 'Untitled';
            $body = trim(implode("\n", array_slice($lines, 1)));
        }

        if ($body === '') {
            $body = $raw;
        }

        return new ArticleDraft(title: $title ?: 'Untitled', bodyMarkdown: $body);
    }
}
