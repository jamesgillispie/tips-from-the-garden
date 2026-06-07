<?php

namespace App\Pipeline\Writers;

use App\Pipeline\Concerns\BuildsArticlePrompts;
use App\Pipeline\Contracts\WriterContract;
use App\Pipeline\Data\ArticleDraft;
use App\Pipeline\Data\WriteRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaWriter implements WriterContract
{
    use BuildsArticlePrompts;

    public function __construct(
        protected string $baseUrl,
        protected string $model,
        protected int $timeout = 600,
    ) {}

    public function write(WriteRequest $request): ArticleDraft
    {
        $raw = $this->chat($this->systemPrompt($request), $this->userPrompt($request));

        return $this->parseDraft($raw);
    }

    public function summarizeStyle(array $samples): string
    {
        $joined = collect($samples)
            ->map(fn (string $sample, int $i) => '<sample id="'.($i + 1).'">'."\n".trim($sample)."\n</sample>")
            ->implode("\n\n");

        return trim($this->chat($this->styleSystemPrompt(), $joined));
    }

    public function identifier(): string
    {
        return 'ollama:'.$this->model;
    }

    protected function chat(string $system, string $user): string
    {
        $response = Http::timeout($this->timeout)
            ->post(rtrim($this->baseUrl, '/').'/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
                'temperature' => 0.7,
                'stream' => false,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                "Ollama request failed ({$response->status()}): ".$response->body()
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('Ollama returned an empty response.');
        }

        return $content;
    }
}
